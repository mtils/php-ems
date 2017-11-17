<?php
/**
 * Created by PhpStorm.
 * User: michi
 * Date: 17.11.17
 * Time: 11:13
 */

namespace Ems\Cache\Categorizer;

use Ems\Core\Helper;

/**
 * Trait KeyMethods
 *
 * This is a little helper to support querying keys with multiple args.
 * This class looks if a method with the first parameter exists and calls
 * that method if you like.
 *
 * Example: Implement a method named addressKey() and this one gets
 * called if you call $categorizer(['address', 35]).
 * The 'address' will be removed from the args before calling it, signature is
 *
 * function addressKey($id)
 *
 * @package Ems\Cache\Categorizer
 */
trait KeyMethods
{

    /**
     * @var array
     */
    protected $_methodCache = [];

    /**
     * Get the key from a method (if it exists)
     *
     * @param $args
     *
     * @return string
     */
    protected function getFromMethod($args)
    {
        if (!$method = $this->getKeyMethod($args)) {
            return '';
        }

        array_shift($args);

        return call_user_func([$this, $method], ...$args);
    }

    /**
     * Get the method responsible for a key (if it exists)
     *
     * @param $args
     *
     * @return string
     */
    protected function getKeyMethod($args)
    {

        if (!is_array($args) || !isset($args[0]) || !is_string($args[0])) {
            return '';
        }

        if (isset($this->_methodCache[$args[0]])) {
            return $this->_methodCache[$args[0]];
        }

        $method = $this->keyMethodName($args[0]);

        $this->_methodCache[$args[0]] = method_exists($this, $method) ? $method : '';

        return $this->_methodCache[$args[0]];
    }

    /**
     * Convert the passed (first) key segment to a method name.
     *
     * @param $first
     *
     * @return string
     */
    protected function keyMethodName($first)
    {
        return Helper::camelCase($first) . 'Key';
    }
}