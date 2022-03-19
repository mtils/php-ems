<?php
/**
 *  * Created by mtils on 29.12.17 at 09:02.
 **/

namespace Ems\Contracts\Core;
use Ems\Contracts\Core\Errors\ConstraintFailure;
use Ems\Contracts\Expression\Constraint;
use Ems\Contracts\Expression\ConstraintGroup;

/**
 * Interface Checker
 *
 * The checker checks if values matches constraints.
 *
 * @package Ems\Contracts\Core
 */
interface Checker extends Extendable
{
    /**
     * Check if $value matches $rule.
     *
     * @param mixed                                   $value
     * @param ConstraintGroup|Constraint|array|string $rule
     * @param object|null                             $ormObject (optional)
     *
     * @return bool
     */
    public function check($value, $rule, $ormObject=null) : bool;

    /**
     * Same as self::check() but throws an exception on false.
     *
     * @param mixed                                     $value
     * @param ConstraintGroup|Constraint|array|string   $rule
     * @param object|null                               $ormObject (optional)
     *
     * @return bool (always true)
     *
     * @throws ConstraintFailure
     */
    public function force($value, $rule, $ormObject=null) : bool;

    /**
     * Return true if the constraint named $name is supported.
     *
     * @param string $name
     *
     * @return bool
     */
    public function supports(string $name) : bool;

    /**
     * Return the names of all constraints.
     *
     * @return string[]
     */
    public function names() : array;

    /**
     * Call a single constraint check. Will call an added extension.
     *
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     */
    public function __call(string $name, array $arguments=[]);

}