<?php

namespace Ems\Core;

use Ems\Contracts\Core\AllProvider;
use Ems\Contracts\Core\TextProvider;
use Ems\Contracts\Core\Named;
use Ems\Contracts\Core\Identifiable;
use Ems\Contracts\Core\AppliesToResource;
use Ems\Core\Support\TypeCheckMethods;
use Ems\Core\Exceptions\ResourceNotFoundException;

class AnythingProvider implements AllProvider
{
    use TypeCheckMethods;

    /**
     * Change this to a class or interface name
     *
     * @var string
     **/
    protected $forceType = 'object';

    /**
     * @var bool
     **/
    protected $typeIsFrozen = true;

    /**
     * @var array
     */
    protected $items = [];

    /**
     * @var array
     */
    protected $classes = [];

    /**
     * @var callable
     **/
    protected $objectCreator;

    /**
     * @var bool
     **/
    protected $allCreated = false;


    /**
     * {@inheritdoc}
     *
     * @param mixed $id
     * @param mixed $default (optional)
     *
     * @return mixed
     **/
    public function get($id, $default = null)
    {
        if (isset($this->items[$id])) {
            return $this->items[$id];
        }

        if (isset($this->classes[$id])) {
            $object = $this->createObject($this->classes[$id]);
            $this->set($id, $object);
            return $object;
        }

        return $default;
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $id
     *
     * @throws \Ems\Contracts\Core\Errors\NotFound
     *
     * @return mixed
     **/
    public function getOrFail($id)
    {
        if ($item = $this->get($id)) {
            return $item;
        }
        throw new ResourceNotFoundException("Resource with id $id not found");
    }

    /**
     * {@inheritdoc}
     *
     * @return array|\Traversable
     **/
    public function all()
    {
        $this->createAllIfNotDone();
        return array_values($this->items);
    }

    /**
     * Set an item for an id
     *
     * @param mixed $id
     * @param mixed $item
     *
     * @return self
     **/
    public function set($id, $item)
    {
        $this->checkType($item);
        $this->items[$id] = $item;
        return $this;
    }

    /**
     * Remove item with the passed id
     *
     * @param mixed $id
     *
     * @throws \Ems\Contracts\Core\Errors\NotFound
     *
     * @return mixed The deleted item
     **/
    public function remove($id)
    {
        $item = $this->getOrFail($id);
        unset($this->items[$id]);
        return $item;
    }

    /**
     * Return an assigned class for $id
     *
     * @param mixed $id
     *
     * @return string|null
     **/
    public function getClass($id)
    {
        return isset($this->classes[$id]) ? $this->classes[$id] : null;
    }

    /**
     * Set a class for an item for deferred loading of items.
     *
     * @param mixed $id
     * @param string $class
     *
     * @return self
     **/
    public function setClass($id, $class)
    {
        $this->classes[$id] = $class;
        if (isset($this->items[$id])) {
            unset($this->items[$id]);
        }
        $this->allCreated = false;
        return $this;
    }

    /**
     * Remove the assigned class $class
     *
     * @param mixed $id
     *
     * @return self
     **/
    public function removeClass($id)
    {
        if (isset($this->classes[$id])) {
            unset($this->classes[$id]);
        }
        if (isset($this->items[$id])) {
            unset($this->items[$id]);
        }
        return $this;
    }

    /**
     * Assign a factory (or an ioc container method) to create objects of the
     * setted classes via setClass()
     *
     * @param callable $creator
     *
     * @return self
     **/
    public function createObjectsWith(callable $creator)
    {
        $this->objectCreator = $creator;
        return $this;
    }

    /**
     * Create an object of $class
     *
     * @param string $class
     *
     * @return object
     **/
    protected function createObject($class)
    {
        if ($this->objectCreator) {
            return call_user_func($this->objectCreator, $class);
        }
        return new $class();
    }

    /**
     * Create all objects of all assigned classes
     **/
    protected function createAllIfNotDone()
    {
        if ($this->allCreated) {
            return;
        }

        foreach ($this->classes as $id=>$class) {
            if (!isset($this->items[$id])) {
                $this->get($id);
            }
        }
        $this->allCreated = true;
    }
}
