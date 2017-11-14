<?php

namespace Ems\Events;


use Ems\Contracts\Core\HasListeners;
use Ems\Contracts\Events\Bus as BusContract;
use Ems\Contracts\Events\EventForward;
use Ems\Core\Exceptions\UnsupportedParameterException;
use Ems\Core\Patterns\HookableTrait;
use Ems\Core\Patterns\SubscribableTrait;
use Ems\Core\Lambda;
use Exception;
use InvalidArgumentException;
use OutOfBoundsException;
use LogicException;
use OverflowException;

/**
 * This event bus supports only events by string
 * comparison. You can fire objects but it will compare
 * the listeners only be absolute class string equality
 **/
class Bus implements BusContract
{
    use HookableTrait {
        HookableTrait::onBefore as parentOnBefore;
        HookableTrait::onAfter as parentOnAfter;
    }

    use SubscribableTrait {
        SubscribableTrait::on as parentOn;
    }

    /**
     * @var array
     **/
    protected $beforeAnyListeners = [];

    /**
     * @var array
     **/
    protected $anyListeners = [];

    /**
     * @var array
     **/
    protected $afterAnyListeners = [];

    /**
     * @var Bus
     **/
    protected $root;

    /**
     * @var string
     **/
    protected $name;

    /**
     * @var array
     **/
    protected $knownMarks = [];

    /**
     * @var MatchingListener
     **/
    protected $matchingListener;

    /**
     * @var callable
     **/
    protected $filter;

    /**
     * @var callable
     **/
    protected $previousFilter;

    /**
     * @var array
     **/
    protected $buses = [];

    /**
     * Bus constructor.
     *
     * @param MatchingListener|null $matchingListener
     * @param string                $name
     * @param Bus|null              $root
     */
    public function __construct(MatchingListener $matchingListener=null, $name='default', Bus $root=null)
    {
        $this->root = $root ?: $this;
        $this->name = $name;
        $this->bused[$name] = $this;
        $this->addKnownMarks();
        $this->useMatchingListener($matchingListener ?: new MatchingListener);
    }

    /**
     * {@inheritdoc}
     *
     * @param string|object $event
     * @param mixed         $payload
     * @param bool          $halt
     *
     * @return mixed
     **/
    public function fire($event, $payload = [], $halt = false)
    {

        if ($this->filter && !call_user_func($this->filter, $event, $payload)) {
            return;
        }

        if (!is_array($payload)) {
            $payload = [$payload];
        }

        $returnValues = [];

        foreach ($this->collectListeners($event, $payload) as $listener) {
            $returnValue = $this->callListener($listener, $event, $payload);

            if ($returnValue !== null && $halt) {
                return $returnValue;
            }

            if ($returnValue === false) {
                $returnValues[] = false;
                return $returnValues;
            }

            $returnValues[] = $returnValue;
        }

        return $returnValues;
    }

    /**
     * {@inheritdoc}
     *
     * @param string|object $event
     * @param mixed         $payload
     * @param bool          $halt
     *
     * @return mixed
     **/
    public function __invoke($event, $payload = [], $halt = false)
    {
        return $this->fire($event, $payload, $halt);
    }

    /**
     * Return the name of this bus. (default: "default")
     *
     * @return string
     **/
    public function name()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     * Pass a asterisk (*) as event name to get the onAll listeners
     *
     * @param string $event
     * @param string $position ('after'|'before'|'')
     *
     * @throws \Ems\Core\Exceptions\UnsupportedParameterException
     *
     * @return array
     **/
    public function getListeners($event, $position = '')
    {
        $event = $this->eventToString($event);

        if (!in_array($position, ['', 'before', 'after'], true)) {
            throw new UnsupportedParameterException('Bus only supports position "","before","after"');
        }

        if ($event != '*') {
            return $position ? $this->getAfterOrBeforeListeners($event, $position) : $this->getOnListeners($event);
        }

        if (!$position) {
            return $this->anyListeners;
        }

        if ($position == 'before') {
            return $this->beforeAnyListeners;
        }

        return $this->afterAnyListeners;

    }

    /**
     * {@inheritdoc}
     *
     * @param string|object $event
     * @param callable      $listener
     *
     * @return self
     **/
    public function onBefore($event, callable $listener)
    {

        if (strpos($event, '*') !== false) {
            $this->beforeAnyListeners[] = $this->toWildcardListener($event, $listener);
            return $this;
        }

        self::parentOnBefore($event, $listener);

        return $this;
    }

     /**
     * {@inheritdoc}
     *
     * @param string   $event
     * @param callable $listener
     *
     * @return self
     **/
    public function on($event, callable $listener)
    {

        if (strpos($event, '*') !== false) {
            $this->anyListeners[] = $this->toWildcardListener($event, $listener);
            return $this;
        }

        self::parentOn($event, $listener);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param string|object $event
     * @param callable      $listener
     *
     * @return self
     **/
    public function onAfter($event, callable $listener)
    {

        if (strpos($event, '*') !== false) {
            $this->afterAnyListeners[] = $this->toWildcardListener($event, $listener);
            return $this;
        }

        self::parentOnAfter($event, $listener);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param string|array $mark
     * @param mixed        $value (optional)
     *
     * @return self
     *
     * @example self::mark('no-broadcast')->fire('users.updated', [])
     **/
     public function mark($mark, $value=null)
     {
        $marks = $this->matchingListener->markToArray($mark, $value, $this->knownMarks);
        return new BusWithMarkSupport($this, $marks);
     }

    /**
     * {@inheritdoc}
     *
     * @param string|array $mark
     * @param mixed        $value (optional)
     *
     * @return self
     *
     * @example self::when('!no-broadcast')->on('users.updated', f())
     **/
     public function when($mark, $value=null)
     {
        $marks = $this->matchingListener->markToArray($mark, $value, $this->knownMarks);
        return new BusWithMarkSupport($this, [], $marks);
     }

     /**
     * {@inheritdoc}
     *
     * @param string|array $mark
     *
     * @return self
     **/
    public function registerMark($mark)
    {
        $this->knownMarks[$mark] = true;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @example Storage::onAfter('persist', self::forward('storage.persist'))
     *
     * @param HasListeners|string $busOrEvent
     * @param string|object       $event (optional)
     *
     * @return EventForward
     **/
    public function forward($busOrEvent, $event=null)
    {
        // If another bus was passed here, we forward from another bus to this
        // one here
        if ($busOrEvent instanceof HasListeners) {
            $forward = new EventForward($busOrEvent, $event);
            return $forward->setTarget($this->root);
        }

        if ($event) {
            throw new LogicException('If you pass no other bus as source, you cant specify an event');
        }

        return new EventForward($this->root, $busOrEvent);
    }

    /**
     * {@inheritdoc}
     *
     * @param callable|string $filter
     *
     * @return self
     *
     * @example self::installFilter('controller.*'); // Will ALLOW only controller. events
     * @example self::installFilter(function ($event, $args) { return false; }) // will block all
     **/
    public function installFilter($filter)
    {
        if (!is_callable($filter)) {
            $f = function () { return true; };
            $filter = $this->wrap($f, $filter);
        }

        $this->filter = $filter;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return self
     **/
    public function uninstallFilter()
    {
        if ($this->isMuted()) {
            $this->previousFilter = null;
            return $this;
        }

        $this->filter = null;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param string|callable $filter
     * @param callable        $run
     **/
    public function filtered($filter, callable $run)
    {
        $this->installFilter($filter);

        try {
            $result = $run();
            $this->filter = null; // No method, or mute will stop to work
            return $result;
        } catch (Exception $e) {
            $this->filter = null;
            throw $e;
        }

    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     **/
    public function hasFilter()
    {
        return is_callable($this->filter);
    }

    /**
     * {@inheritdoc}
     *
     * @param bool $mute (default=true)
     *
     * @return self
     **/
    public function mute($mute=true)
    {
        if (!$mute) {
            $this->filter = $this->previousFilter;
            $this->previousFilter = null;
            return $this;
        }

        $this->previousFilter = $this->filter;
        $this->installFilter('!');

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     **/
    public function isMuted()
    {
        if (!$this->filter instanceof MatchingListener) {
            return false;
        }

        return $this->filter->getPattern() === '!';
    }

    /**
     * {@inheritdoc}
     *
     * @param callable $run
     *
     * @return mixed.
     **/
    public function muted(callable $run)
    {
        return $this->filtered('!', $run);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $name
     *
     * @return self
     *
     * @throws OutOfBoundsException (if bus not found)
     **/
    public function bus($name)
    {
        if ($this->name != 'default') {
            return $this->root->bus($name);
        }

        if (!isset($this->buses[$name])) {
            throw new OutOfBoundsException("Bus '$name' not found.");
        }

        return $this->buses[$name];
    }

    /**
     * {@inheritdoc}
     *
     * @param string          $name
     * @param string|callable $forwardFilter (optional)
     *
     * @return self
     **/
    public function addBus($name, $forward=null)
    {
        if ($this->name != 'default') {
            return $this->root->addBus($name);
        }

        if ($name == 'default') {
            throw new LogicException('You cannot add a bus named "default"');
        }

        if (isset($this->buses[$name])) {
            throw new OverflowException("A Bus named '$name' already exists. Remove it first before adding a new one with this name.");
        }

        $this->buses[$name] = new static($this->matchingListener, $name, $this);

        return $this->buses[$name];

    }

    /**
     * {@inheritdoc}
     *
     * @param string $name
     *
     * @return self
     *
     * @throws OutOfBoundsException (if bus not found)
     **/
    public function removeBus($name)
    {
        if ($this->name != 'default') {
            $this->root->removeBus($name);
            return $this;
        }

        $bus = $this->bus($name);
        unset($this->buses[$name]);
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $name
     *
     * @return bool
     **/
    public function hasBus($name)
    {
        if ($this->name != 'default') {
            return $this->root->hasBus($name);
        }
        return isset($this->buses[$name]);
    }

    /**
     * Return the MatchingListener this bus uses.
     *
     * @return MatchingListener
     **/
    public function usedMatchingListener()
    {
        return $this->matchingListener;
    }

    /**
     * For wildcard and mark matching a listener proxy is used. If you
     * need to change the behaviour of that proxy, assign here a
     * different one.
     *
     * @param MatchingListener $matchingListener
     *
     * @retrun self
     **/
    public function useMatchingListener(MatchingListener $matchingListener)
    {
        $this->matchingListener = $matchingListener;
        return $this;
    }

    /**
     * Collect all listeners for event $event
     *
     * @param string|object $event
     * @param array         $args
     *
     * @return array
     **/
    protected function collectListeners($event, array $args=[])
    {
        $event = $this->eventToString($event);

        $listeners = [];

        // Build args for * listeners
        array_unshift($args, $event);

        foreach ($this->root->getListeners('*', 'before') as $listener) {
            $listeners[] = function () use ($listener, $event, &$args) {
                return $this->callListener($listener, $event, $args);
            };
        }

        foreach ($this->root->getListeners($event, 'before') as $listener) {
            $listeners[] = $listener;
        }

        foreach ($this->root->getListeners($event) as $listener) {
            $listeners[] = $listener;
        }

        foreach ($this->root->getListeners('*') as $listener) {
            $listeners[] = function () use ($listener, $event, &$args) {
                return $this->callListener($listener, $event, $args);
            };
        }

        foreach ($this->root->getListeners($event, 'after') as $listener) {
            $listeners[] = $listener;
        }

        foreach ($this->root->getListeners('*', 'after') as $listener) {
            $listeners[] = function () use ($listener, $event, &$args) {
                return $this->callListener($listener, $event, $args);
            };
        }

        return $listeners;
    }

    /**
     * Make a string out of an event
     *
     * @param object|string $event
     *
     * @return string
     **/
    protected function eventToString($event)
    {
        return is_object($event) ? get_class($event) : $event;
    }

    /**
     * Calls a listener.
     *
     * @param callable $listener
     * @param string   $event
     * @param array    $args
     *
     * @return mixed
     **/
    protected function callListener(callable $listener, $event, array $args)
    {
        return Lambda::callFast($listener, $args);
    }

    protected function addKnownMarks()
    {
        foreach ([static::FROM_REMOTE, static::PREVIOUS, static::SOURCE, static::NO_BROADCAST, static::FROM_BATCH] as $known) {
            $this->knownMarks[$known] = true;
        }
    }

    protected function toWildcardListener($event, callable $listener)
    {
        if ($event === '*') {
            return $listener;
        }

        if (!$listener instanceof MatchingListener) {
            $listener = $this->wrap($listener);
        }

        return $listener->setPattern($event);
    }

    protected function wrap(callable $listener, $pattern='*', array $markFilters=[])
    {
        return $this->matchingListener->newInstance($listener, $pattern, $markFilters);
    }

    /**
     * Check if the event is supported. The bus accepts all types of
     * events
     *
     * @param string $event
     *
     * @throws \Ems\Contracts\Core\Errors\UnSupported
     **/
    protected function checkEvent($event)
    {
    }
}
