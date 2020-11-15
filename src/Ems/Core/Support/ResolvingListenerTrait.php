<?php

namespace Ems\Core\Support;

use Ems\Contracts\Core\Map;
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
     * Calls all resolving and afterResolving listeners and return the instance.
     *
     * @param string $abstract
     * @param object $instance
     *
     * @return object
     */
    protected function callAllListeners($abstract, $instance)
    {
        $class = is_object($instance) ? get_class($instance) : '';

        $excludeBefore = [];
        $excludeAfter = [];

        $this->callListeners($abstract, $instance, $this->resolvingListeners, $excludeBefore);
        $this->callListeners($abstract, $instance, $this->resolvedListeners, $excludeAfter);

        if (!$class || $class == $abstract) {
            return $instance;
        }

        $this->callListeners($abstract, $instance, $this->resolvingListeners, $excludeBefore);
        $this->callListeners($abstract, $instance, $this->resolvedListeners, $excludeAfter);

        return $instance;
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
     * @param string          $abstract
     * @param callable|string $listener
     *
     * @return self
     **/
    public function storeAfterResolvingListener(string $abstract, $listener)
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
            Map::callVoid($listeners[$abstract], [$result, $this]);
            $excludes[$abstract] = true;
        }

        foreach ($listeners as $classOrInterface => $instanceListeners) {

            // We already called them above
            if ($classOrInterface == $abstract || isset($excludes[$classOrInterface]) || !$result instanceof $classOrInterface) {
                continue;
            }

            Map::callVoid($instanceListeners, [$result, $this]);

            $excludes[$classOrInterface] = true;
        }
    }

}
