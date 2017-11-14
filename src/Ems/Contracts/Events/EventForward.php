<?php

namespace Ems\Contracts\Events;

use Closure;
use Ems\Contracts\Core\Hookable;
use Ems\Contracts\Core\Subscribable;
use Ems\Contracts\Core\HasListeners;
use Ems\Contracts\Core\HasMethodHooks;
use Ems\Core\Exceptions\UnsupportedUsageException;
use Ems\Core\Helper;
use InvalidArgumentException;
use LogicException;


/**
 * The EventForward is used to forward or map events between
 * Buses, Hookables or Subscribables.
 * It does a lot of magic to gets you a nice api for your bus mixing console ;-)
 *
 * This object is a one-time-usage event. After a call of ->to() you cannot
 * change the object. This is because it adds the listeners to the source and
 * cannot remove them afterwards.
 **/
class EventForward
{

    /**
     * @var HasListeners
     **/
    protected $source;

    /**
     * @var Bus
     **/
    protected $target;

    /**
     * @var array
     **/
    protected $sourcePatterns = [];

    /**
     * @var string
     **/
    protected $targetPattern = '';

    /**
     * @var bool
     **/
    private $isFixed = false;

     /**
     * @param HasListeners $source (optional)
     * @param string|array $event (optional)
     *
     **/
    public function __construct(HasListeners $source=null, $event=null)
    {
        if ($source) {
            $this->from($source, $event);
        }
    }

    /**
     * Get the (emitting) source. Events are produced here.
     *
     * @return HasListeners
     **/
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Get the (event receiving) target.
     *
     * @return Bus
     **/
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * @param Bus $bus
     *
     * @return self
     **/
    public function setTarget(Bus $bus)
    {
        $this->failIfFixed();
        $this->target = $bus;
        return $this;
    }

    /**
     * Set the source or the source event (pattern) which should be forwarded.
     * From without a to dos nothing.
     *
     * Forward all events of a HasMethodHooks object to a bus:
     * @example $forward->from($hasMethodHooks)->to($bus, 'storage.*')
     *
     * Forward a special hook of a HasMethodHooks object to a bus
     * @example $forward->from($hasMethodHooks, 'persist')->to($bus, 'storage.persisted')
     *
     * Forward some hooks of a HasMethodHooks object to a bus
     * @example $forward->from($hasMethodHooks, ['persist', 'purge'])->to($bus, 'storage.*')
     *
     * Forward a distinct event of a Subscribable to a bus:
     * @example $forward->from($subscribable, 'error')->to($bus, 'database.error')
     *
     * Forward a distinct event of an Bus to another bus:
     * @example $forward->from($bus, 'user.updated')->to($userBus, 'updated')
     *
     * Forward a distinct event with another name to the same bus:
     * @example $forward->from($bus, 'user.updated')->to($bus, 'orm.updated')
     *
     * @param HasListeners $source
     * @param string|array $event (optional)
     *
     * @return self
     **/
    public function from(HasListeners $source, $event=null)
    {

        $this->failIfFixed();

        $this->source = $source;

        // Collect which events should be forwarded
        $events = $event ? (array)$event : [];

        if (!$events && $source instanceof HasMethodHooks) {
            $events = $source->methodHooks();
        }

        $this->sourcePatterns = $events;

        return $this;

    }

    /**
     * Determine the target. Pass a Bus and it will be sent to that bus. Pass
     * just an event name and it will be sent to the source. (If its a bus)
     *
     *
     * @param Bus|string $targetOrEvent
     * @param string     $event (optional)
     *
     * @return void
     **/
    public function to($targetOrEvent, $event=null)
    {

        $this->failIfFixed();

        $this->target = $this->findTargetByArguments($targetOrEvent);

        $this->checkSourceAndPattern();

        if ($targetOrEvent instanceof HasListeners && !$event) {
            $event = '*';
        }

        $targetEventName = $event ? $event : $targetOrEvent;

        if (!is_string($targetEventName)) {
            throw new LogicException("Cannot map to unkown target event type: " . Helper::typeName($targetEventName));
        }

        if (!$this->source instanceof Subscribable) {
            $this->listenToHookable($targetEventName);
            return;
        }

        if (!$this->source instanceof Bus) {
            $this->listenToSubscribable($targetEventName);
            return;
        }

        $this->listenToBus($targetEventName);
        return;

    }

    /**
     * An Event Forward has to be callable. If it has a target and a target
     * event name, this will just trigger target->fire().
     *
     * Because of this you can just use Hookable::onAfter('event', Bus::forward('event.fired'))
     *
     * @return mixed
     **/
    public function __invoke()
    {

        if (!$this->source instanceof Bus) {
            throw new LogicException('You need a source bus to call an EventForward');
        }

        if (!$this->sourcePatterns) {
            throw new LogicException('You cannot call an EventFilter without source events');
        }

        $lastResult = null;

        foreach ($this->sourcePatterns as $event) {

            if ($this->isPattern($event)) {
                throw new LogicException('You cannot call an EventFilter with patterns');
            }

            $lastResult = $this->source->fire($event, func_get_args());
        }

        return $lastResult;

    }

    /**
     * Creates the target event name out of a pattern.
     * The logic is the following:
     *
     * A target pattern '*' just copies the event name completely
     * into the target bus.
     *
     * @example self::from($bus, 'users.*')->to('*') / users.stored = users.stored
     *
     * A target pattern '.*' copies exactly one segment into the target
     * bus. The segments a choosen from right left:
     *
     * @example self::from($bus, 'users.*')->to('user.*') // users.stored = user.stored
     * @example self::from($bus, 'orm.*')->to('repository.*') // orm.users.stored = repository.stored
     * @example self::from($bus, 'orm.*')->to('repository.*.*') // orm.users.stored = repository.users.stored
     *
     * @param string $sourceEvent
     * @param string $targetPattern
     *
     * @return string
     **/
    public function buildTargetEventName($sourceEvent, $targetPattern)
    {
        if (!$this->isPattern($targetPattern)) {
            return $targetPattern;
        }

        if ($targetPattern == '*') {
            return $sourceEvent;
        }

        $patternParts = array_reverse(explode('.', $targetPattern));
        $eventParts = array_reverse(explode('.', $sourceEvent));

        $parsed = [];

        foreach ($patternParts as $i=>$part) {

            if ($part != '*') {
                $parsed[] = $part;
                continue;
            }

            if (isset($eventParts[$i])) {
                $parsed[] = $eventParts[$i];
            }
        }

        return implode('.', array_reverse($parsed));
    }

    /**
     * Check if the event name is pattern.
     *
     * @param string $event
     *
     * @return bool
     **/
    protected function isPattern($event)
    {
        return strpos($event, '*') !== false;
    }

    /**
     * @param string $event
     * @param string $pattern
     *
     * @return bool
     **/
    public function matchesPattern($event, $pattern)
    {
        if ($pattern === '*') {
            return true;
        }

        if ($event == $pattern) {
            return true;
        }

        return fnmatch($pattern, $event);
    }

    /**
     * Helps the to method to find the right target.
     *
     * @param mixed $targetOrEvent
     *
     * @return Bus
     **/
    protected function findTargetByArguments($targetOrEvent)
    {

        if ($targetOrEvent instanceof Bus) {
            return $targetOrEvent;
        }

        if ($this->target instanceof Bus) {
            return $this->target;
        }

        if ($this->source instanceof Bus) {
            return $this->source;
        }

        throw new LogicException('No suitable source found to map event. No target passed, no target setted, source is no Bus. Giving up.');
    }

    /**
     * Checks if patterns are used on a not pattern supporting source
     **/
    protected function checkSourceAndPattern()
    {

        if (!$this->sourcePatterns) {
            throw new LogicException("No source events (or patterns) found, Call from() before calling to()");
        }

        // Bus supports patterns
        if ($this->source instanceof Bus) {
            return;
        }

        foreach ($this->sourcePatterns as $pattern) {
            if ($this->isPattern($pattern)) {
                throw new LogicException("Source has to be a bus to listen to patterns");
            }
        }
    }

    /**
     * Installs a listener on a (not subscribable) source.
     *
     * @param string $targetEventName
     **/
    protected function listenToHookable($targetEventName)
    {
        foreach ($this->sourcePatterns as $sourceEvent) {
            $listener = $this->newHookableListener($sourceEvent, $targetEventName);
            $this->source->onAfter($sourceEvent, $listener);
        }
        $this->isFixed = true;
    }

    /**
     * Installs a listener on a subscribable (not bus) source.
     *
     * @param string $targetEventName
     **/
    protected function listenToSubscribable($targetEventName)
    {
        foreach ($this->sourcePatterns as $event) {
            $listener = $this->newSubscribableListener($event, $targetEventName);
            $this->source->on($event, $listener);
        }
        $this->isFixed = true;
    }

    /**
     * Installs a listener on a subscribable (not bus) source.
     *
     * @param string $targetEventName
     **/
    protected function listenToBus($targetEventName)
    {
        foreach ($this->sourcePatterns as $pattern) {
            $listener = $this->newBusListener($pattern, $targetEventName);
            $this->source->on('*', $listener);
        }
        $this->isFixed = true;
    }

    /**
     * Creates a listener for a hookable object
     *
     * @param string $sourceEvent
     * @param string $targetEventName
     *
     * @return Closure
     **/
    protected function newHookableListener($sourceEvent, $targetEventName)
    {
        return function () use ($sourceEvent, $targetEventName) {
            $args = func_get_args();
            $targetEventName = $this->buildTargetEventName($sourceEvent, $targetEventName);
            return $this->target->fire($targetEventName, $args);
        };
    }

    /**
     * Creates a listener for a subscribable object
     *
     * @param string $event
     * @param string $targetEventName
     *
     * @return Closure
     **/
    protected function newSubscribableListener($event, $targetEventName)
    {
        return function () use ($targetEventName, $event) {
            $args = func_get_args();
            $targetEventName = $this->buildTargetEventName($event, $targetEventName);
            return $this->target->fire($targetEventName, $args);
        };
    }

    /**
     * Creates a listener for a bus.
     *
     * @param string $sourcePattern
     * @param string $targetEventName
     *
     * @return Closure
     **/
    protected function newBusListener($sourcePattern, $targetEventName)
    {
        return function () use ($targetEventName, $sourcePattern) {

            $args = func_get_args();
            $originalEventName = array_shift($args);

            // We do the wildcard matching by ourselfs here...
            if (!$this->matchesPattern($originalEventName, $sourcePattern)) {
                return null;
            }


            $targetEventName = $this->buildTargetEventName($originalEventName, $targetEventName);

            return $this->target->fire($targetEventName, $args);
        };
    }

    /**
     * Throw an exception if the class if fixed.
     *
     **/
    protected function failIfFixed()
    {
        if ($this->isFixed) {
            throw new UnsupportedUsageException('This class did install the listeners, you cant change it anymore');
        }
    }

}
