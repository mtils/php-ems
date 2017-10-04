<?php


namespace Ems\Core\Support;

use Ems\Core\Lambda;
use Ems\Core\Exceptions\UnConfiguredException;
use ReflectionClass;

/**
 * @see \Ems\Contracts\Core\SupportsCustomFactory
 **/
trait CustomFactorySupport
{

    /**
     * @var callable
     **/
    protected $_customFactory;

    /**
     * {@inheritdoc}
     *
     * @param callable $factory
     *
     * @return self
     **/
    public function createObjectsBy(callable $factory)
    {
        $this->_customFactory = $factory;
        return $this;
    }

    /**
     * Create the object via the custom callable
     *
     * @param string $abstract (optional) class or interface name
     * @param array  $parameters (optional)
     *
     * @return object
     **/
    protected function createObject($abstract=null, array $parameters=[])
    {

        $abstract = $this->factoryAbstract($abstract);

        if (!$this->_customFactory) {
            return $this->createWithoutFactory($abstract, $parameters);
        }

        return Lambda::callFast($this->_customFactory, [$abstract, $parameters]);
    }

    /**
     * Create the object by yourself.
     *
     * @param string $abstract (class or interface)
     * @param array  $parameters (optional)
     *
     * @return object
     **/
    protected function createWithoutFactory($abstract, array $parameters=[])
    {
        return (new ReflectionClass($abstract))->newInstanceArgs($parameters);
    }

    /**
     * Return the factory abstract name if no one was passed to self::createObject()
     * Just assign a property named $factoryAbstract and write the class or
     * interface name into it. So you dont have to pass it everytime.
     *
     * @param string $abstract (optional)
     *
     * @return string
     **/
    protected function factoryAbstract($abstract=null)
    {

        if ($abstract) {
            return $abstract;
        }

        if (property_exists($this, 'factoryAbstract')) {
            return $this->factoryAbstract;
        }

        throw new UnConfiguredException("Please assign a factory abstract to this class or pass one. No idea what object to create.");
    }
}
