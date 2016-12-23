<?php

namespace Ems\Core\Support;

trait IOCHelperMethods
{
    /**
     * {@inheritdoc}
     *
     * @param string $abstract
     * @param array  $parameters (optional)
     *
     * @throws \OutOfBoundsException
     *
     * @return object
     **/
    public function make($abstract, array $parameters = [])
    {
        return $this->__invoke($abstract, $parameters);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $abstract
     * @param bool   $processParameters (optional)
     *
     * @return \Closure
     **/
    public function provide($abstract, $processParameters = false)
    {
        if (!$processParameters) {
            return function () use ($abstract) {
                return $this->__invoke($abstract);
            };
        }

        return function () use ($abstract) {
            return $this->__invoke($abstract, func_get_args());
        };
    }
}
