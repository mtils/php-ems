<?php


namespace Ems\Events\Laravel;

use Ems\Contracts\Events\Bus;
use Illuminate\Contracts\Events\Dispatcher;
use Ems\Core\Exceptions\NotImplementedException;

/**
 * Use the ems event dispatcher as a laravel dispatcher
 **/
class EventDispatcher implements Dispatcher
{
    /**
     * @var Bus
     **/
     protected $events;

    /**
     * @param Bus $events
     **/
    public function __construct(Bus $events)
    {
        $this->events = $events;
    }

    /**
     * {@inheritdoc}
     *
     * @param  string|array  $events
     * @param  mixed  $listener
     * @return void
     */
    public function listen($events, $listener=null): void
    {
        $this->events->on($events, $listener);
    }

    /**
     * {@inheritdoc}
     *
     * @param  string  $eventName
     * @return bool
     */
    public function hasListeners($eventName)
    {
        if ($listeners = $this->events->getListeners($eventName)) {
            return true;
        }

        if ($listeners = $this->events->getListeners($eventName, 'before')) {
            return true;
        }

        if ($listeners = $this->events->getListeners($eventName, 'after')) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     *
     * @param  string  $event
     * @param  array  $payload
     * @return void
     */
    public function push($event, $payload = [])
    {
        $this->listen($event.'_pushed', function () use ($event, $payload) {
            $this->fire($event, $payload);
        });
    }

    /**
     * {@inheritdoc}
     *
     * @param  object|string  $subscriber
     * @return void
     */
    public function subscribe($subscriber)
    {
        if (is_object($subscriber)) {
            $subscriber->subscribe($this);
            return;
        }
        $this->subscribe(new $subscriber);
    }

    /**
     * {@inheritdoc}
     *
     * @param  string  $event
     * @param  array  $payload
     * @return mixed
     */
    public function until($event, $payload = [])
    {
        return $this->fire($event, $payload, true);
    }

    /**
     * {@inheritdoc}
     *
     * @param  string  $event
     * @return void
     */
    public function flush($event)
    {
        $this->fire($event.'_pushed');
    }

    /**
     * {@inheritdoc}
     *
     * @param  string|object  $event
     * @param  mixed  $payload
     * @param  bool  $halt
     * @return array|null
     */
    public function fire($event, $payload = [], $halt = false)
    {
        return $this->events->fire($event, $payload, $halt);
    }

    /**
     * {@inheritdoc}
     *
     * @param  string|object  $event
     * @param  mixed  $payload
     * @param  bool  $halt
     * @return array|null
     */
    public function dispatch($event, $payload = [], $halt = false)
    {
        return $this->events->fire($event, $payload, $halt);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function firing()
    {
        throw new NotImplementedException('Method firing() is not supported by this dispatcher');
    }

    /**
     * {@inheritdoc}
     *
     * @param  string  $event
     * @return void
     */
    public function forget($event)
    {
        throw new NotImplementedException('Method forget() is not supported by this dispatcher');
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function forgetPushed()
    {
        throw new NotImplementedException('Method forgetPushed() is not supported by this dispatcher');
    }
}
