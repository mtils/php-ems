<?php
/**
 *  * Created by mtils on 29.12.17 at 09:02.
 **/

namespace Ems\Contracts\Core;
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
     * @param AppliesToResource                       $resource (optional)
     *
     * @return bool
     */
    public function check($value, $rule, AppliesToResource $resource=null);

    /**
     * Same as self::check() but throws an exception on false.
     *
     * @param mixed                        $value
     * @param ConstraintGroup|array|string $rule
     * @param AppliesToResource            $resource (optional)
     *
     * @return bool (always true)
     *
     * @throws \Ems\Contracts\Core\Errors\ConstraintFailure
     */
    public function force($value, $rule, AppliesToResource $resource=null);

    /**
     * Return true if the constraint named $name is supported.
     *
     * @param string $name
     *
     * @return bool
     */
    public function supports($name);

    /**
     * Return the names of all constraints.
     *
     * @return array
     */
    public function names();

    /**
     * Call a single constraint check. Will call an added extension.
     *
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     */
    public function __call($name, array $arguments=[]);

}