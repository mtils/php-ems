<?php

namespace Ems\Core;

use Ems\Contracts\Core\NamedQuantity;
use Ems\Contracts\Core\AppliesToResource;

class NamedObject implements NamedQuantity, AppliesToResource
{
    /**
     * @var mixed
     **/
    protected $id;

    /**
     * @var string
     **/
    protected $name = '';

    /**
     * @var string
     **/
    protected $resourceName;

    /**
     * @var int
     **/
    protected $count = 0;

    /**
     * @param mixed  $id   (optional)
     * @param string $name (optional)
     **/
    public function __construct($id = null, $name = '', $resourceName = '')
    {
        $this->id = $id;
        $this->name = $name;
        $this->resourceName = $resourceName;
    }

    /**
     * {@inheritdoc}
     *
     * @return mixed (int|string)
     *
     * @see \Ems\Contracts\Core\Identifiable
     **/
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     *
     * @see \Ems\Contracts\Core\Named
     **/
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     *
     * @see \Ems\Contracts\Core\AppliesToResource
     **/
    public function resourceName()
    {
        return $this->resourceName;
    }

    /**
     * Set the id.
     *
     * @param mixed $id
     *
     * @return self
     **/
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Set the name.
     *
     * @param string $name
     *
     * @return self
     **/
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Set the resource name.
     *
     * @param string $resourceName
     *
     * @return self
     **/
    public function setResourceName($resourceName)
    {
        $this->resourceName = $resourceName;

        return $this;
    }

    /**
     * Return the containing ItemCount.
     *
     * @return int
     *
     * @see \Ems\Contracts\Core\NamedQuantity
     **/
    public function count()
    {
        return $this->count;
    }

    /**
     * Set the count (quantity) of this object.
     *
     * @param int $count
     *
     * @return self
     *
     * @see \Ems\Contracts\Core\NamedQuantity
     **/
    public function setCount($count)
    {
        $this->count = $count;

        return $this;
    }
}
