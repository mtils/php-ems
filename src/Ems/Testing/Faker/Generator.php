<?php
/**
 *  * Created by mtils on 04.12.2021 at 14:39.
 **/

namespace Ems\Testing\Faker;

use Ems\Core\Exceptions\HandlerNotFoundException;
use Faker\Generator as BaseGenerator;

use function class_exists;
use function implode;
use function str_replace;

class Generator extends BaseGenerator
{
    /**
     * @var array
     */
    protected $instanceFactories = [];

    /**
     * @var array
     */
    protected $instanceFactoryNamespaces = [];

    /**
     * @var string
     */
    protected $instanceFactorySuffix = 'Factory';

    /**
     * Create an instance of $clas
     *
     * @param string $class
     * @return object
     *
     * @throws HandlerNotFoundException
     */
    public function instance(string $class)
    {
        return $this->getInstanceFactory($class)->instance($class, $this);
    }

    /**
     * Create $quantity instances of $class.
     *
     * @param string $class
     * @param int $quantity
     * @return object[]
     */
    public function instances(string $class, int $quantity) : array
    {
        $instances = [];
        for ($i=0; $i<$quantity; $i++) {
            $instances[] = $this->instance($class);
        }
        return $instances;
    }

    /**
     * Generate data to create a $class.
     *
     * @param string $class
     * @return array
     */
    public function attributes(string $class) : array
    {
        return $this->getInstanceFactory($class)->data($class, $this);
    }

    /**
     * Return an instance factory for $class.
     *
     * @param string $class
     * @return InstanceFactory
     */
    public function getInstanceFactory(string $class) : InstanceFactory
    {
        if (isset($this->instanceFactories[$class])) {
            return $this->instanceFactories[$class];
        }

        $checkedClasses = [];
        foreach ($this->instanceFactoryNamespaces as $classNamespace=>$factoryNamespace) {
            $relativeClass = trim(str_replace($classNamespace, '', $class), '\\');
            $factoryClass = $factoryNamespace . '\\' . $relativeClass . $this->instanceFactorySuffix;
            $checkedClasses[] = $factoryClass;
            if (!class_exists($factoryClass)) {
                continue;
            }
            $this->instanceFactories[$class] = new $factoryClass;
            return $this->instanceFactories[$class];
        }
        throw new HandlerNotFoundException("No factory found $class. Checked: " . implode($checkedClasses));
    }

    /**
     * Set an instance factory manually.
     *
     * @param string $class
     * @param InstanceFactory $factory
     * @return $this
     */
    public function setInstanceFactory(string $class, InstanceFactory $factory) : Generator
    {
        $this->instanceFactories[$class] = $factory;
        return $this;
    }

    /**
     * Map a class namespace to a factory namespace.
     *
     * @param string $classNamespace
     * @param string $factoryNamespace
     * @return $this
     */
    public function mapInstanceFactoryNamespace(string $classNamespace, string $factoryNamespace) : Generator
    {
        $this->instanceFactoryNamespaces[$classNamespace] = $factoryNamespace;
        return $this;
    }

    /**
     * Remove the namespace for factories
     * @param string $namespace
     * @return $this
     */
    public function removeInstanceFactoryNamespace(string $namespace) : Generator
    {
        foreach ($this->instanceFactoryNamespaces as $classNamespace=>$factoryNamespace) {
            if ($factoryNamespace == $namespace) {
                unset($this->instanceFactoryNamespaces[$classNamespace]);
            }
        }

        return $this;
    }


}