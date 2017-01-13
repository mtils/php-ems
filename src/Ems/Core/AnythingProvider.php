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
}
