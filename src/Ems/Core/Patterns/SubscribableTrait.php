<?php

namespace Ems\Core\Patterns;

use Ems\Core\Exceptions\UnsupportedParameterException;
use Ems\Core\Helper;

/**
 * @see \Ems\Contracts\Core\Subscribable
 **/
trait SubscribableTrait
{
    /**
     * @var array
     **/
    protected $listeners = [];

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

        if (is_object($event) || strpos($event,'\\')) { // looks like a class
            throw new UnsupportedParameterException('Only string based events are supported');
        }

        if (!isset($this->listeners[$event])) {
            $this->listeners[$event] = [];
        }
        $this->listeners[$event][] = $listener;

        return $this;
    }

    /**
     * {@inheritdoc}
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
        if ($position !== '') {
            throw new UnsupportedParameterException('SubscribableTrait only supports an empty position');
        }
        return $this->getOnListeners($event);
    }

    /**
     * @param string $event
     * @param string $position ('after'|'before'|'')
     *
     * @throws \Ems\Contracts\Core\Errors\UnSupported
     *
     * @return array
     **/
    protected function getOnListeners($event)
    {
        if (isset($this->listeners[$event])) {
            return $this->listeners[$event];
        }

        return [];
    }

    /**
     * Calls the assigned before listeners.
     *
     * @param string $event
     * @param array  $args
     *
     * @return bool
     **/
    protected function callOnListeners($event, $args = [])
    {
        $result = false;
        foreach ($this->getOnListeners($event) as $listener) {
            Helper::call($listener, $args);
        }
        return $result;
    }

}
