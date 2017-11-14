<?php


namespace Ems\Expression;


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
            $keyConstraintArray = $this->explodeConstraints($keyConstraints);

            $parsedConstraints[$key] = [];

            foreach ($keyConstraintArray as $keyConstraint) {
                list($name, $parameters) = $this->nameAndParameters($keyConstraint);
                $parsedConstraints[$key][$name] = $parameters;
            }
        }

        return $parsedConstraints;
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

}
