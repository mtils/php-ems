<?php

namespace Ems\Contracts\Core;

/**
 * The event dispatcher can produce unknown and unexisting events
 * via a fire method.
 * The EMS EventDispatcher has no listen priority, listen onBefore, on or
 * onAfter. These are 3 priorities, what makes this class much simpler and the
 * usage of it (a least for me) more ovbious while using the dispatcher.
 * The event dispatcher should not support wildcard listeners.
 * Use the onAll(f()) method and match the event by yourself.
 **/
interface EventDispatcher extends Hookable, Subscribable
{
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
     * Alias for self::fire(). If you really need to need a fire dependency in
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
     * Get informed on all events. Be careful to not make any expensive
     * operations on every call.
     *
     * @param callable $listener
     *
     * @return self
     **/
    public function onAll(callable $listener);
}
