<?php

namespace Ems\Contracts\Core;

/**
 * This is basically the base interface of Hookable|Subscribable
 **/
interface HasListeners
{
    /**
     * Return all listeners for event $event. Ask for position be $position
     * 'after' or 'before'.
     *
     * @param string|object $event
     * @param string $position ('after'|'before'|'')
     *
     * @return array
     **/
    public function getListeners($event, $position = '');
}
