<?php

namespace Ems\Core;

use Ems\Contracts\Core\TemporalQuantity;
use Ems\Contracts\Core\AppliesToResource;

class GenericTemporalQuantity extends PointInTime implements TemporalQuantity, AppliesToResource
{
    /**
     * @var string
     **/
    protected $resourceName;

    /**
     * @var int
     **/
    protected $count = 0;

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
    #[\ReturnTypeWillChange]
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
