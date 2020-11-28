<?php

namespace Ems\Core\Support;

use Ems\Contracts\Core\ContainerCallable;
use Ems\Contracts\Core\IOCContainer;
use OutOfBoundsException;

trait IOCHelperMethods
{
    /**
     * @see IOCContainer::__invoke()
     *
     * @param string $abstract
     * @param array $parameters (optional)
     *
     * @return object
     *
     * @throws OutOfBoundsException
     */
    public function __invoke($abstract, array $parameters = [])
    {
        if (!$parameters) {
            return $this->get($abstract);
        }
        return $this->create($abstract, $parameters);
    }

    /**
     * Create a shared binding (singleton). Omit the factory to let the container
     * create one for you.
     *
     * @param string               $abstract
     * @param callable|string|null $factory
     *
     * @return void
     */
    public function share(string $abstract, $factory=null)
    {
        if (!$factory) {
            $factory = function () use ($abstract) {
                return $this->create($abstract);
            };
        }
        $this->bind($abstract, $factory, true);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $abstract
     * @param string $method (optional)
     *
     * @return ContainerCallable
     **/
    public function provide(string $abstract, string $method = '')
    {
        return new ContainerCallable($this, $abstract, $method);
    }
}
