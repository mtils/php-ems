<?php

namespace Ems\Core\Support;

trait ResolvingListenerTrait
{
    /**
     * @var array
     **/
    protected $resolvingListeners = [];

    /**
     * @var array
     **/
    protected $resolvedListeners = [];

    /**
     * Calls all resolving and afterResolving listeners.
     *
     * @param string $abstract
     * @param object $instance
     */
    protected function callAllListeners($abstract, $instance)
    {
        $this->callListeners($abstract, $instance, $this->resolvingListeners);
        $this->callListeners($abstract, $instance, $this->resolvedListeners);
    }

    /**
     * Stores a listener in the resolving array.
     *
     * @param string   $abstract
     * @param callable $listener
     *
     * @return self
     **/
    protected function storeResolvingListener($abstract, $listener)
    {
        if (!isset($this->resolvingListeners[$abstract])) {
            $this->resolvingListeners[$abstract] = [];
        }
        $this->resolvingListeners[$abstract][] = $listener;

        return $this;
    }

    /**
     * Stores a listener in the resolved array.
     *
     * @param string   $abstract
     * @param callable $listener
     *
     * @return self
     **/
    public function storeAfterResolvingListener($abstract, $listener)
    {
        if (!isset($this->resolvedListeners[$abstract])) {
            $this->resolvedListeners[$abstract] = [];
        }
        $this->resolvedListeners[$abstract][] = $listener;

        return $this;
    }

    /**
     * Calls the assigned listeners.
     *
     * @param string $abstract
     * @param object $result
     * @param array  $listeners
     **/
    protected function callListeners($abstract, $result, array $listeners)
    {
        if (isset($listeners[$abstract])) {
            $this->iterateListeners($listeners[$abstract], $result);
        }

        foreach ($listeners as $classOrInterface => $instanceListeners) {

            // We already called them above
            if ($classOrInterface == $abstract) {
                continue;
            }

            if (!$result instanceof $classOrInterface) {
                continue;
            }

            $this->iterateListeners($instanceListeners, $result);
        }
    }

    /**
     * Remove one level of indention in callListeners ;-).
     *
     * @param array  $listeners
     * @param object $result
     **/
    protected function iterateListeners(array $listeners, $result)
    {
        foreach ($listeners as $listener) {
            call_user_func($listener, $result, $this);
        }
    }
}
