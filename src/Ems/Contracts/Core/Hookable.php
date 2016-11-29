<?php

namespace Ems\Contracts\Core;

/**
 * The Hookable interface assures that you can hook before or after a
 * method or an event was performed.
 * So if your object has for example a method 'perform' you could hook into it
 * via onBefore('perform', f()) or onAfter('perform', d()).
 * In the events cant be seen as method hooks there is no need to check
 * for event existance. You can also support object based events and
 * collect the listeners by ($fired instanceof $$event).
 * Throw an Unsupported if you do not support object based events.
 **/
interface Hookable
{
    /**
     * Be informed before event (or method) $event is triggered.
     *
     * @param string|object   $event
     * @param callable $listener
     *
     * @return self
     **/
    public function onBefore($event, callable $listener);

    /**
     * Be informed before event (or method) $event is triggered.
     *
     * @param string|object   $event
     * @param callable $listener
     *
     * @return self
     **/
    public function onAfter($event, callable $listener);

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
