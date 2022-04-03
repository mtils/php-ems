<?php
/**
 *  * Created by mtils on 29.12.17 at 10:33.
 **/

namespace Ems\Core\Patterns;


use Ems\Contracts\Core\Type;
use Ems\Core\Exceptions\NotImplementedException;
use function property_exists;

trait SnakeCaseCallableMethods
{
    /**
     * @var array
     */
    protected $snakeCaseMethods;

    /**
     * Get the real name of the method which is callable by (snake cased) $name.
     *
     * @param string $name
     *
     * @return string
     */
    protected function getMethodBySnakeCaseName(string $name) : string
    {
        $methods = $this->getSnakeCaseMethods();
        return $methods[$name] ?? '';
    }

    /**
     * Get all snake case methods, indexed by its snake case name.
     *
     * @return string[]
     */
    protected function getSnakeCaseMethods() : array
    {
        if ($this->snakeCaseMethods !== null) {
            return $this->snakeCaseMethods;
        }

        if (!property_exists($this, 'snakeCasePrefix')) {
            throw new NotImplementedException('Implement snakeCasePrefix to make snake case support work.');
        }

        $prefix = $this->snakeCasePrefix;

        foreach (get_class_methods($this) as $method) {

            if (!$this->isSnakeCaseCallableMethod($method, $prefix)) {
                continue;
            }

            $this->snakeCaseMethods[$this->methodToSnakeCase($method, $prefix)] = $method;
        }

        return $this->snakeCaseMethods;
    }

    /**
     * Return true if the method is a snake case callable method.
     *
     * @param string $method
     * @param string $prefix
     *
     * @return bool
     */
    protected function isSnakeCaseCallableMethod(string $method, string $prefix) : bool
    {
        if ($this->isIgnoredSnakeCaseCallableMethod($method)) {
            return false;
        }
        return $method != $prefix && strpos($method, $prefix) === 0;
    }

    /**
     * Overwrite this method to filter out some your methods.
     *
     * @param string $method
     * @return bool
     */
    protected function isIgnoredSnakeCaseCallableMethod(string $method) : bool
    {
        return false;
    }

    /**
     * Converts a validation method name to a rule name
     *
     * @param string $methodName
     * @param string $prefix
     *
     * @return string
     **/
    protected function methodToSnakeCase(string $methodName, string $prefix) : string
    {
        return Type::snake_case(substr($methodName, strlen($prefix)));
    }
}