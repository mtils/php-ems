<?php
/**
 *  * Created by mtils on 28.11.20 at 09:25.
 **/

namespace Ems\Core\Patterns;

use function call_user_func;
use function get_class;
use function is_object;

/**
 * Class ListenerContainer
 *
 * This is a helper class to implement the Hookable and Subscribable interfaces.
 *
 * @see \Ems\Contracts\Core\Hookable
 * @see \Ems\Contracts\Core\Subscribable
 *
 * @package Ems\Core\Patterns
 */
class ListenerContainer
{
    const ON = 'on';

    const BEFORE = 'before';

    const AFTER = 'after';

    const POSITIONS = [
        self::BEFORE,
        self::ON,
        self::AFTER
    ];

    /**
     * @var array
     */
    private $listeners = [];

    /**
     * @var bool
     */
    private $muted = false;

    /**
     * Add a $listener to $event on $position.
     *
     * @param string $event
     * @param callable $listener
     * @param string $position (default: on)
     */
    public function add(string $event, callable $listener, $position=self::ON)
    {
        if (!isset($this->listeners[$position])) {
            $this->listeners[$position] = [];
        }
        if (!isset($this->listeners[$position][$event])) {
            $this->listeners[$position][$event] = [];
        }
        $this->listeners[$position][$event][] = $listener;
    }

    /**
     * Get all listeners for $event at $position.
     *
     * @param string $event
     * @param string $position
     *
     * @return callable[]
     */
    public function get(string $event, $position=self::ON)
    {
        if (!isset($this->listeners[$position])) {
            return [];
        }
        if (!isset($this->listeners[$position][$event])) {
            return [];
        }
        return $this->listeners[$position][$event];
    }

    /**
     * Get all listeners that were registered for an interface $concrete implements
     * or a parent class of $concrete.
     *
     * @param string $abstract
     * @param mixed $concrete
     * @param string $position (default: 'on')
     * @param array $collected (optional)
     *
     * @return callable[]
     */
    public function getByInheritance(string $abstract, $concrete, string $position=self::ON, &$collected=[])
    {
        if (!isset($this->listeners[$position])) {
            return [];
        }

        $listeners = [];

        foreach ($this->listeners[$position] as $event=>$eventListeners) {

            if (isset($collected[$event])) {
                continue;
            }

            if ($event !== $abstract && !$concrete instanceof $event) {
                continue;
            }

            foreach ($eventListeners as $listener) {

                $listeners[] = $listener;
                $collected[$event] = true;
            }

        }

        return $listeners;

    }

    /**
     * Call every listener of $event with $args.
     *
     * @param string            $event
     * @param array             $args
     * @param string|string[]   $positions
     *
     * @return bool
     */
    public function call(string $event, array $args=[], $positions=self::ON)
    {
        $called = false;
        foreach ((array)$positions as $position) {
            foreach ($this->get($event, $position) as $listener) {
                call_user_func($listener, ...$args);
                $called = true;
            }
        }

        return $called;
    }

    /**
     * Call every listener of $abstract and assume that there are listeners that
     * listen on interfaces or parent class names.
     *
     * @param string            $abstract
     * @param mixed             $concrete
     * @param array             $args (optional)
     * @param string|string[]   $positions (default: 'on')
     *
     * @return bool
     */
    public function callByInheritance(string $abstract, $concrete, array $args=[], $positions=self::ON)
    {
        if ($this->muted) {
            return false;
        }
        $called = false;
        $excludes = [];
        $positions = (array)$positions;
        $class = is_object($concrete) ? get_class($concrete) : '';

        foreach ($positions as $position) {
            $excludes[$position] = [];
        }
        foreach ($positions as $position) {
            foreach ($this->getByInheritance($abstract, $concrete, $position, $excludes[$position]) as $listener) {
                call_user_func($listener, ...$args);
                $called = true;
            }
        }

        if (!$class || $class == $abstract) {
            return $called;
        }

        foreach ($positions as $position) {
            foreach ($this->getByInheritance($abstract, $concrete, $position, $excludes[$position]) as $listener) {
                call_user_func($listener, ...$args);
                $called = true;
            }
        }

        return $called;

    }

    /**
     * Call $run without running the normal hooks assigned to this listener container.
     *
     * @param callable $run
     * @return mixed
     */
    public function secretly(callable $run)
    {
        $this->muted = true;
        $result = $run();
        $this->muted = false;
        return $result;
    }
}