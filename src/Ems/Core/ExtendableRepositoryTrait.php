<?php


namespace Ems\Core;

use OutOfRangeException;
use OutOfBoundsException;

/**
 * @see \Ems\Contracts\Core\ExtendableRepository
 **/
trait ExtendableRepositoryTrait
{

    protected $_listeners = [
        'getting'       => [],
        'got'           => [],
        'made'          => [],
        'storing'       => [],
        'stored'        => [],
        'filling'       => [],
        'filled'        => [],
        'updating'      => [],
        'updated'       => [],
        'saving'        => [],
        'saved'         => [],
        'deleting'      => [],
        'deleted'       => []
    ];

    /**
     * {@inheritdoc}
     *
     * @param callable $listener
     * @return self
     **/
    public function getting(callable $listener)
    {
        $this->_listeners[__FUNCTION__][] = $listener;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param callable $listener
     * @return self
     **/
    public function got(callable $listener)
    {
        $this->_listeners[__FUNCTION__][] = $listener;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param callable $listener
     * @return self
     **/
    public function made(callable $listener)
    {
        $this->_listeners[__FUNCTION__][] = $listener;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param callable $listener
     * @return self
     **/
    public function storing(callable $listener)
    {
        $this->_listeners[__FUNCTION__][] = $listener;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param callable $listener
     * @return self
     **/
    public function stored(callable $listener)
    {
        $this->_listeners[__FUNCTION__][] = $listener;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param callable $listener
     * @return self
     **/
    public function filling(callable $listener)
    {
        $this->_listeners[__FUNCTION__][] = $listener;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param callable $listener
     * @return self
     **/
    public function filled(callable $listener)
    {
        $this->_listeners[__FUNCTION__][] = $listener;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param callable $listener
     * @return self
     **/
    public function updated(callable $listener)
    {
        $this->_listeners[__FUNCTION__][] = $listener;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param callable $listener
     * @return self
     **/
    public function saving(callable $listener)
    {
        $this->_listeners[__FUNCTION__][] = $listener;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param callable $listener
     * @return self
     **/
    public function saved(callable $listener)
    {
        $this->_listeners[__FUNCTION__][] = $listener;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param callable $listener
     * @return self
     **/
    public function deleting(callable $listener)
    {
        $this->_listeners[__FUNCTION__][] = $listener;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param callable $listener
     * @return self
     **/
    public function deleted(callable $listener)
    {
        $this->_listeners[__FUNCTION__][] = $listener;
        return $this;
    }

    /**
     * Call this method to publish your actions. The first param is the name
     * (e.g. updating, saving) the rest are the args
     *
     * @param string $method (updating|saving|...)
     * @param mixed $arg1 (optional)
     * @param mixed $arg2 (optional)
     * @param mixed $arg3 (optional)
     * @param mixed $arg4 (optional)
     * @param mixed $arg5 (optional)
     * @return null
     **/
    protected function publish($method, $arg1=null, $arg2=null, $arg3=null, $arg4=null, $arg5=null)
    {
        $args = func_get_args();
        array_shift($args);

        if (!isset($this->_listeners[$method])) {
            throw new OutOfBoundsException("Unknown method '$method'");
        }

        switch (count($args)) {
            case 1:
                array_map(function($listener) use ($args) {
                    call_user_func($listener, $args[0]);
                }, $this->_listeners[$method]);
                return;
            case 2:
                array_map(function($listener) use ($args) {
                    call_user_func($listener, $args[0], $args[1]);
                }, $this->_listeners[$method]);
                return;
            case 3:
                array_map(function($listener) use ($args) {
                    call_user_func($listener, $args[0], $args[1], $args[2]);
                }, $this->_listeners[$method]);
                return;
            case 4:
                array_map(function($listener) use ($args) {
                    call_user_func($listener, $args[0], $args[1], $args[2], $args[3]);
                }, $this->_listeners[$method]);
                return;
            case 5:
                array_map(function($listener) use ($args) {
                    call_user_func($listener, $args[0], $args[1], $args[2], $args[3], $args[4]);
                }, $this->_listeners[$method]);
                return;

        }

        throw new OutOfRangeException("Cant handle arg count of " . count($args));

    }

}