<?php

namespace Ems\Core\Support;

use Ems\Contracts\Core\ContainerCallable;

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
     * @param string $method (optional)
     *
     * @return ContainerCallable
     **/
    public function provide($abstract, $method = '')
    {
        return new ContainerCallable($this, $abstract, $method);
    }
}
