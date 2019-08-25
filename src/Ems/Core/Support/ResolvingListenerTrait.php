<?php

namespace Ems\Core\Support;

use function get_class;
use function is_object;

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
        $class = is_object($instance) ? get_class($instance) : '';

        $excludeBefore = [];
        $excludeAfter = [];

        $this->callListeners($abstract, $instance, $this->resolvingListeners, $excludeBefore);
        $this->callListeners($abstract, $instance, $this->resolvedListeners, $excludeAfter);

        if (!$class || $class == $abstract) {
            return;
        }

        $this->callListeners($abstract, $instance, $this->resolvingListeners, $excludeBefore);
        $this->callListeners($abstract, $instance, $this->resolvedListeners, $excludeAfter);
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
     * @param array $excludes
     **/
    protected function callListeners($abstract, $result, array $listeners, array &$excludes)
    {
        if (isset($listeners[$abstract]) && !isset($excludes[$abstract])) {
            $this->iterateListeners($listeners[$abstract], $result);
            $excludes[$abstract] = true;
        }

        foreach ($listeners as $classOrInterface => $instanceListeners) {

            // We already called them above
            if ($classOrInterface == $abstract) {
                continue;
            }

            if (isset($excludes[$classOrInterface])) {
                continue;
            }

            if (!$result instanceof $classOrInterface) {
                continue;
            }

            $this->iterateListeners($instanceListeners, $result);
            $excludes[$classOrInterface] = true;
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
