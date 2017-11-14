<?php

namespace Ems\Contracts\Core;

interface Subscribable extends HasListeners
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

}
