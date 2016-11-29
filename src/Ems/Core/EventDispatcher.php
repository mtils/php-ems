<?php


namespace Ems\Core;


use Ems\Contracts\Core\EventDispatcher as DispatcherContract;
use Ems\Core\Exceptions\UnsupportedParameterException;
use Ems\Core\Patterns\HookableTrait;
use Ems\Core\Patterns\SubscribableTrait;
use Ems\Core\Helper;


/**
 * This event dispatcher supports only events by string
 * comparison. You can fire objects but it will compare
 * the listeners only be absolute class string equality
 **/
class EventDispatcher implements DispatcherContract
{

    use HookableTrait;
    use SubscribableTrait;

    /**
     * @var array
     **/
    protected $allListeners = [];

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

        if (!is_array($payload)) {
            $payload = [$payload];
        }

        $returnValues = [];

        foreach ($this->collectListeners($event) as $listener) {

            $returnValue = Helper::call($listener, $payload);

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

        if (!in_array($position, ['','before','after'], true)) {
            throw new UnsupportedParameterException('EventDispatcher only supports position "","before","after"');
        }

        if ($event == '*') {
            return $this->allListeners;
        }

        if (!$position) {
            return $this->getOnListeners($event);
        }

        return $this->getAfterOrBeforeListeners($event, $position);

    }

    /**
     * {@inheritdoc}
     *
     * @param callable $listener
     * @return self
     **/
    public function onAll(callable $listener)
    {
        $this->allListeners[] = $listener;
        return $this;
    }

    /**
     * Collect all listeners for event $event
     *
     * @param string|object $event
     *
     * @return array
     **/
    protected function collectListeners($event)
    {

        $event = $this->eventToString($event);

        $listeners = [];

        foreach ($this->allListeners as $listener) {
            $listeners[] = function() use ($event, $listener) {
                $args = func_get_args();
                array_unshift($args, $event);
                return Helper::call($listener, $args);
            };
        }

        foreach ($this->getListeners($event, 'before') as $listener) {
            $listeners[] = $listener;
        }

        foreach ($this->getListeners($event) as $listener) {
            $listeners[] = $listener;
        }

        foreach ($this->getListeners($event, 'after') as $listener) {
            $listeners[] = $listener;
        }

        return $listeners;
    }

    /**
     * Make a string out of an event
     *
     * @param object|string $event
     * @return string
     **/
    protected function eventToString($event)
    {
        return is_object($event) ? get_class($event) : $event;
    }

    /**
     * Check if the event is supported. The dispatcher accepts all types of
     * events
     *
     * @param string $event
     * @throws \Ems\Contracts\Core\Errors\UnSupported
     **/
    protected function checkEvent($event)
    {
    }

}
