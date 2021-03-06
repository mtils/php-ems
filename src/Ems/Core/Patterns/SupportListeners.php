<?php

namespace Ems\Core\Patterns;

use Ems\Core\Lambda;

trait SupportListeners
{
    /**
     * @var array
     **/
    protected $listeners = [];

    /**
     * Stores a listener in the resolving array.
     *
     * @param string   $event
     * @param callable $listener
     *
     * @return self
     **/
    protected function storeListener($event, callable $listener)
    {
        if (!isset($this->listeners[$event])) {
            $this->listeners[$event] = [];
        }
        $this->listeners[$event][] = $listener;

        return $this;
    }

    /**
     * Calls the assigned listeners.
     *
     * @param string $event
     * @param array  $args
     *
     * @return bool
     **/
    protected function callListeners($event, $args = [])
    {
        if (!$this->hasListeners($event)) {
            return false;
        }

        foreach ($this->listeners[$event] as $listener) {
            $this->callListener($listener, is_array($args) ? $args : [$args]);
        }

        return true;
    }

    /**
     * Calls all listeners regardless of their names.
     *
     * @param array $args
     */
    protected function callAllListeners($args = [])
    {
        $args = (array) $args;
        array_map(function ($event) use ($args) {
            $this->callListeners($event, $args);
        }, array_keys($this->listeners));
    }

    /**
     * Does event $event has listeners?
     *
     * @param string $event
     *
     * @return bool
     **/
    protected function hasListeners($event)
    {
        return isset($this->listeners[$event]);
    }

    /**
     * Call one listener.
     *
     * @param callable $listener
     * @param array    $args
     *
     * @return mixed
     **/
    protected function callListener(callable $listener, array $args = [])
    {
        return Lambda::callFast($listener, $args);
    }
}
