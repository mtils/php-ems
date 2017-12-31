<?php


namespace Ems\Expression;


use function is_array;

trait ConstraintParsingMethods
{
    /**
     * Parses the constraints for easier usage.
     *
     * @param array $constraints
     *
     * @return array
     **/
    protected function parseConstraints(array $constraints)
    {
        $parsedConstraints = [];

        foreach ($constraints as $key=>$keyConstraints) {
            $parsedConstraints[$key] = $this->parseConstraint($keyConstraints);
        }

        return $parsedConstraints;
    }

    /**
     * Parses the constraints for easier usage.
     *
     * @param mixed $rule
     *
     * @return array
     **/
    protected function parseConstraint($rule)
    {
        $parsed = [];

        // If someone added a native (associative) array.
        if (is_array($rule) && !isset($rule[0])) {
            return $this->normalizeNativeRule($rule);
        }

        $constraints = $this->explodeConstraints($rule);

        foreach ($constraints as $constraint) {
            list($name, $parameters) = $this->nameAndParameters($constraint);
            $parsed[$name] = $parameters;
        }

        return $parsed;
    }

    /**
     * Split the constraints in an array
     *
     * @param array|string $keyConstraints
     *
     * @return array
     **/
    protected function explodeConstraints($keyConstraints)
    {
        return is_string($keyConstraints) ? explode('|', $keyConstraints) : (array)$keyConstraints;
    }

    /**
     * Split a constraint into its name and an array of parameters
     *
     * @param string $constraint
     *
     * @return array
     **/
    protected function nameAndParameters($constraint)
    {
        if (!strpos($constraint, ':')) {
            return [$this->normalizeConstraintName($constraint), []];
        }

        list($name, $parameters) = explode(':', $constraint, 2);

        return [$this->normalizeConstraintName($name), explode(',', $parameters)];
    }

    /**
     * Ensure underscores
     *
     * @param string $name
     *
     * @return string
     **/
    protected function normalizeConstraintName($name)
    {
        return str_replace('-', '_', mb_strtolower($name));
    }

    /**
     * Make every parameters to array if they are not already arrays.
     *
     * @param array $rule
     *
     * @return array
     */
    protected function normalizeNativeRule(array $rule)
    {
        $normalized = [];
        foreach ($rule as $key=>$parameters) {
            $normalized[$key] = is_array($parameters) ? $parameters : [$parameters];
        }
        return $normalized;
    }
}
