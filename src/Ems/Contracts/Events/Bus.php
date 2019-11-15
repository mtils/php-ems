<?php

namespace Ems\Contracts\Events;

use Ems\Contracts\Core\HasListeners;
use Ems\Contracts\Core\Hookable;
use Ems\Contracts\Core\Subscribable;

/**
 * The event bus can produce unknown and unexisting events
 * via a fire method.
 * The EMS Event Bus has no listen priority, listen onBefore, on or
 * onAfter. These are 3 priorities, what makes this class much simpler and the
 * usage of it (a least for me) more obvious while using the bus.
 * You can use onBefore('*'), on('*') and onAfter('*') to listen to all events.
 * But be careful: This is going very expensive if done a lot.
 *
 * Its the same with wildcard listeners. Wildcard listeners are added with
 * a proxy as "all" listeners. The proxy is then called on every event and
 * calls your callable only if its pattern matches.
 *
 * The mark and when methods are tools to get the many situations under control
 * where events are fired but should not in some special cases. Or to avoid
 * endless recursion.
 * If for example you use a broadcaster to broadcast events to another server,
 * you would mark them to not broadcast them again.
 * Or in some batch operation you would mark the events as "in-batch", so that
 * listeners can decide to only listen on single updates and so on.
 *
 * Solving this with wildcards would not be the best idea, because wildcards are
 * making everything slower.
 *
 **/
interface Bus extends Hookable, Subscribable
{

    /**
     * Marks an event that it comes from remote (server or process or whatever)
     * The value of this mark is boolean. All events without that mark are
     * local.
     *
     * @var string
     *
     * @example self::mark('from-remote')->fire('users.updated')
     **/
    const FROM_REMOTE = 'from-remote';

    /**
     * Marks an event that it was refired from another event. The previous event
     * name is held in that mark.
     *
     * @var string
     *
     * @example self::mark('previous', 'users.created')->fire('users.updated')
     **/
    const PREVIOUS = 'previous';

    /**
     * Marks an event as coming from a distinct source.
     *
     * @var string
     *
     * @example self::mark('source', 'broadcaster')->fire('users.updated')
     **/
    const SOURCE = 'source';

    /**
     * Marks an event as should not be broadcasted.
     *
     * @var string
     *
     * @example self::mark('no-broadcast')->fire('users.updated')
     **/
    const NO_BROADCAST = 'no-broadcast';

    /**
     * Marks an event as produced by a batch operation.
     *
     * @var string
     *
     * @example self::mark('from-batch')->fire('users.updated')
     **/
    const FROM_BATCH = 'from-batch';

    /**
     * Marks an event as produced by a processing queue.
     *
     * @var string
     *
     * @example self::mark('from-queue')->fire('users.updated')
     **/
    const FROM_QUEUE = 'from-queue';

    /**
     * Fire $event. The event can be a string (which is the
     * normal case) or an object. The object does not have to be an
     * Event Object. The container for example should fire the created
     * objects.
     * If halt is true return the first result which is !== null. If not
     * return all return values in an array.
     *
     * @param string|object $event
     * @param mixed         $payload
     * @param bool          $halt
     *
     * @return mixed
     **/
    public function fire($event, $payload = [], $halt = false);

    /**
     * Alias for self::fire(). If you really do need a fire dependency in
     * your class typehint against callable and you can use this object as your
     * firing callable.
     *
     * @param string|object $event
     * @param mixed         $payload
     * @param bool          $halt
     *
     * @return mixed
     **/
    public function __invoke($event, $payload = [], $halt = false);

    /**
     * Return the name of this bus. (default: "default")
     *
     * @return string
     **/
    public function name();

    /**
     * Mark a fired event with a mark. A mark can have a value. If not it is just
     * marked as true.
     * It is forbidden to mark as false. when('!mark') means the mark is not
     * setted, not that it has to be setted false.
     * An indexed array mark(['remote', 'refired']) means you want to mark
     * multiple marks as true.
     * An assoziative array sets multiple marks at once.
     * In general you cannot determine which listeners will be called, the
     * listeners deceide when the want to be called.
     *
     * @param string|array $mark
     * @param mixed        $value (optional)
     *
     * @return self
     *
     * @example self::mark('no-broadcast')->fire('users.updated', [])
     **/
     public function mark($mark, $value=null);

    /**
     * Listen only on event with the passed mark filters. A leading ! in the mark
     * name means only if this mark was not setted.
     * So this is equal: when('!remote')->on() || when('remote', false)->on()
     * Same as in mark: An indexed array means all the marks have to be setted,
     * an assoziative array sets multiple filters at once.
     *
     * @param string|array $mark
     * @param mixed        $value (optional)
     *
     * @return self
     *
     * @example self::when('!no-broadcast')->on('users.updated', f())
     **/
     public function when($mark, $value=null);

     /**
     * Register a new mark. Only registered marks can be used.
     * A not registered mark will throw an UnSupported Exception in when()
     * and mark().
     *
     * @param string|array $mark
     *
     * @return self
     **/
    public function registerMark($mark);

    /**
     * Returns a callable to be used to forward hooks on ths bus.
     *
     * @example Storage::onAfter('persist', self::forward('storage.persist'))
     *
     * @param HasListeners|string $busOrEvent
     * @param string|object       $event (optional)
     *
     * @return EventForward
     **/
    public function forward($busOrEvent, $event=null);

    /**
     * Install a custom filter. This filter will be called in every fire process.
     * If the filter returns false, the event will not be fired.
     * Add just a wildcard pattern to add a wildcard filter.
     * You can only have one filter.
     *
     * @param callable|string $filter
     *
     * @return self
     *
     * @example self::installFilter('controller.*'); // Will ALLOW only controller. events
     * @example self::installFilter(function ($event, $args) { return false; }) // will block all
     **/
    public function installFilter($filter);

    /**
     * Uninstall a previously installed filter. If no filter was installed, just
     * ignore the call.
     *
     * @return self
     **/
    public function uninstallFilter();

    /**
     * Install a filter, run the code in $run, then disable the filter again.
     * The return value of the callable is returned
     *
     * @param string|callable $filter
     * @param callable        $run
     *
     * @return mixed
     **/
    public function filtered($filter, callable $run);

    /**
     * Return if the Bus currently is filtered.
     *
     * @return bool
     **/
    public function hasFilter();

    /**
     * Completely mute the bus. In muted state no events will be fired. Unmute
     * it with mute(false)
     *
     * @param bool $mute (default=true)
     *
     * @return self
     **/
    public function mute($mute=true);

    /**
     * Return true if the bus is currently muted.
     *
     * @return bool
     **/
    public function isMuted();

    /**
     * Mute the bus, run some code and unmute it again. The return value of the
     * callable is returned.
     *
     * @param callable $run
     *
     * @return mixed.
     **/
    public function muted(callable $run);

    /**
     * Return the (previously added) bus named $name.
     *
     * @param string $name
     *
     * @return self
     *
     * @throws \OutOfBoundsException (if bus not found)
     **/
    public function bus($name);

    /**
     * Add a new event bus with name $name. Different buses are used instead of
     * wildcard matching events. Split your bus into some parts of your application.
     * This will give you a faster, more predictable and simpler Event System.
     * Optionally you can forward Events to that bus.
     *
     * @see self::forward
     *
     * @example Bus::forward('orm.*')->to(Bus::addBus('orm'))
     *
     * @param string          $name
     *
     * @return self (The newly created bus)
     **/
    public function addBus($name);

    /**
     * Remove the (previously added) bus $bus.
     *
     * @param string $name
     *
     * @return self
     *
     * @throws \OutOfBoundsException (if bus not found)
     **/
    public function removeBus($name);

    /**
     * Return true if someone added a bus named $name.
     *
     * @param string $name
     *
     * @return bool
     **/
    public function hasBus($name);

}
