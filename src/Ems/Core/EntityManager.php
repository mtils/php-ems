<?php
/**
 *  * Created by mtils on 06.02.18 at 18:33.
 **/

namespace Ems\Core;

use function call_user_func;
use Ems\Contracts\Core\EntityManager as EntityManagerContract;
use Ems\Contracts\Core\EntityPointer;
use Ems\Contracts\Core\Identifiable;
use Ems\Contracts\Core\Provider;
use Ems\Contracts\Core\SupportsCustomFactory;
use Ems\Core\Exceptions\HandlerNotFoundException;
use Ems\Core\Exceptions\MissingArgumentException;
use Ems\Core\Exceptions\ResourceNotFoundException;
use Ems\Core\Patterns\ExtendableByClassHierarchyTrait;
use Ems\Core\Support\CustomFactorySupport;
use Ems\Contracts\Core\Errors\NotFound;
use function get_class;
use InvalidArgumentException;
use function is_string;

class EntityManager implements EntityManagerContract, SupportsCustomFactory
{
    use ExtendableByClassHierarchyTrait;
    use CustomFactorySupport;

    /**
     * This array is used to store the sequence of registered providers in
     * reversed order so that later registered handlers are called first.
     *
     * @var array
     */
    protected $registeredTypes = [];

    /**
     * @var array
     */
    protected $providerFactories = [];

    /**
     * @var array
     */
    protected $providers = [];

    /**
     * {@inheritdoc}
     *
     * @param string|EntityPointer $classOrPointer
     * @param string|int           $id (optional)
     *
     * @return Identifiable|null
     */
    public function get($classOrPointer, $id=null)
    {

        list($class, $id) = $this->toClassAndId($classOrPointer, $id);

        try {
            $provider = $this->provider($class);
            return $provider->get($id);
        } catch (HandlerNotFoundException $e) {
        }

        if (!$extension = $this->nearestForClass($class)) {
            return null;
        }

        try {
            return call_user_func($extension, $class, $id);
        } catch (NotFound $e) {
            return null;
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param string|EntityPointer $classOrPointer
     * @param string|int           $id (optional)
     *
     * @return Identifiable
     *
     * @throws \Ems\Contracts\Core\Errors\NotFound
     */
    public function getOrFail($classOrPointer, $id=null)
    {
        if ($entity = $this->get($classOrPointer, $id)) {
            return $entity;
        }

        list($class, $id) = $this->toClassAndId($classOrPointer, $id);

        throw new ResourceNotFoundException("Entity $class:$id not found");
    }

    /**
     * {@inheritdoc}
     *
     * @param string $class
     *
     * @return Provider
     *
     * @throws \Ems\Contracts\Core\Errors\NotFound
     */
    public function provider($class)
    {

        $class = ltrim($class, '\\');

        if (isset($this->providers[$class])) {
            return $this->providers[$class];
        }

        if (!isset($this->providerFactories[$class])) {
            throw new HandlerNotFoundException("No provider for class $class found.");
        }

        $this->providers[$class] = call_user_func($this->providerFactories[$class], $class);

        return $this->providers[$class];

    }

    /**
     * {@inheritdoc}
     *
     * @param string                   $class
     * @param string|Provider|callable $provider
     *
     * @return self
     */
    public function setProvider($class, $provider)
    {

        ltrim($class, '\\');

        if ($provider instanceof Provider) {
            $this->providers[$class] = $provider;
            return $this;
        }

        if (is_callable($provider)) {
            $this->providerFactories[$class] = $provider;
            return $this;
        }

        if (!is_string($provider)) {
            throw new InvalidArgumentException("Provider has to be callable, Provider or a class name.");
        }

        $this->providerFactories[$class] = function () use ($provider) {
            return $this->createObject($provider);
        };

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param Identifiable $object
     *
     * @return EntityPointer
     */
    public function pointer(Identifiable $object)
    {
        $class = get_class($object);

        try {
            $this->provider($class);
            return new EntityPointer($class, $object->getId());
        } catch (HandlerNotFoundException $e) {
        }

        if ($extension = $this->nearestForClass($class)) {
            return new EntityPointer($class, $object->getId());
        }

        return null;

    }

    /**
     * @param $classOrPointer
     * @param string|int  $id
     *
     * @return array
     */
    protected function toClassAndId($classOrPointer, $id=null)
    {
        if ($classOrPointer instanceof EntityPointer) {
            return [ltrim($classOrPointer->type, '\\'), $classOrPointer->id];
        }

        if (!$id) {
            throw new MissingArgumentException('If $classOrPointer is not an EntityPointer you have to pass an id.');
        }

        return [ltrim($classOrPointer, '\\'), $id];
    }
}