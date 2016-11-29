<?php

namespace Ems\Core\Patterns;

use Ems\Contracts\Core\HasMethodHooks;
use Ems\Core\Exceptions\NotImplementedException;
use Ems\Core\Exceptions\UnsupportedParameterException;
use Ems\Core\Helper;

/**
 * @see \Ems\Contracts\Core\Hookable
 **/
trait HookableTrait
{
    /**
     * @var array
     **/
    protected $beforeListeners = [];

    /**
     * @var array
     **/
    protected $afterListeners = [];

    /**
     * {@inheritdoc}
     *
     * @param string|object   $event
     * @param callable $listener
     *
     * @return self
     **/
    public function onBefore($event, callable $listener)
    {

        $this->checkEvent($event);

        if (!isset($this->beforeListeners[$event])) {
            $this->beforeListeners[$event] = [];
        }
        $this->beforeListeners[$event][] = $listener;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param string|object   $event
     * @param callable $listener
     *
     * @return self
     **/
    public function onAfter($event, callable $listener)
    {
        $this->checkEvent($event);

        if (!isset($this->afterListeners[$event])) {
            $this->afterListeners[$event] = [];
        }
        $this->afterListeners[$event][] = $listener;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param string|object $event
     * @param string $position ('after'|'before'|'')
     *
     * @return array
     **/
    public function getListeners($event, $position = '')
    {
        return $this->getAfterOrBeforeListeners($event, $position);
    }

    /**
     * @param string|object $event
     * @param string $position ('after'|'before'|'')
     *
     * @throws \Ems\Contracts\Core\Errors\UnSupported
     *
     * @return array
     **/
    protected function getAfterOrBeforeListeners($event, $position)
    {
        if (!in_array($position, ['before','after'])) {
            throw new UnsupportedParameterException("Unsupported event position '$position'");
        }

        if ($position == 'before' && isset($this->beforeListeners[$event])) {
            return $this->beforeListeners[$event];
        }

        if ($position == 'after' && isset($this->afterListeners[$event])) {
            return $this->afterListeners[$event];
        }

        return [];
    }

    /**
     * Calls the assigned before listeners.
     *
     * @param string|object $event
     * @param array  $args
     *
     * @return bool
     **/
    protected function callBeforeListeners($event, $args = [])
    {
        return $this->callListeners($this->getListeners($event, 'before'), $args);
    }

    /**
     * Calls the assigned after listeners.
     *
     * @param string|object $event
     * @param array  $args
     *
     * @return bool
     **/
    protected function callAfterListeners($event, $args = [])
    {
        return $this->callListeners($this->getListeners($event, 'after'), $args);
    }

    /**
     * Calls the assigned after listeners.
     *
     * @param array $listeners
     * @param array  $args
     *
     * @return bool
     **/
    protected function callListeners(array $listeners, $args = [])
    {
        $called = false;
        foreach ($listeners as $listener) {
            Helper::call($listener, is_array($args) ? $args : [$args]);
            $called = true;
        }

        return $called;
    }

    /**
     * Check if the event is supported. Throw an exception if this object
     * has method hooks
     *
     * @param string $event
     * @throws \Ems\Contracts\Core\Errors\UnSupported
     **/
    protected function checkEvent($event)
    {

        if (is_object($event) || strpos($event,'\\')) { // looks like a class
            throw new UnsupportedParameterException('Only string based events are supported');
        }

        if (!$this instanceof HasMethodHooks) {
            return;
        }

        if (!in_array($event, $this->methodHooks())) {
            throw new NotImplementedException("Event or method '$event' is not hookable");
        }
    }

}
