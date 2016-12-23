<?php

namespace Ems\Contracts\Core;

interface Subscribable
{
    /**
     * Subscribe to event $event.
     *
     * @param string|object $event
     * @param callable      $listener
     *
     * @return self
     **/
    public function on($event, callable $listener);

    /**
     * Return all listeners for event $event. Ask for position be $position
     * 'after' or 'before'.
     *
     * @param string|object $event
     * @param string        $position ('after'|'before'|'')
     *
     * @return array
     **/
    public function getListeners($event, $position = '');
}
