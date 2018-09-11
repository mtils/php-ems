<?php

namespace Ems\Core;

use Ems\Contracts\Core\Entity;
use Ems\Contracts\Core\AppliesToResource;
use Ems\Contracts\Core\ArrayWithState as ArrayWithStateContract;
use Ems\Core\Support\TrackedArrayDataTrait;

/**
 * Class FakeEntity
 *
 * If you really need only a stub object in which you can fake the new and
 * modified states use this one.
 *
 * @package Ems\Core
 */
class FakeEntity extends GenericEntity
{
    /**
     * @var bool|null
     **/
    protected $isNew;

    /**
     * @var bool|null
     **/
    protected $modified = false;

    /**
     * {@inheritdoc}
     *
     * @return bool
     **/
    public function isNew()
    {
        return $this->isNew !== null ? $this->isNew : parent::isNew();
    }

    /**
     * {@inheritdoc}
     *
     * @param string|array $attributes (optional)
     *
     * @return bool
     **/
    public function wasModified($attributes=null)
    {
        return $this->modified !== null ? $this->modified : parent::wasModified($attributes);
    }

    /**
     * @param bool|null $new (default:true)
     *
     * @return self
     **/
    public function makeNew($new=true)
    {
        $this->isNew = $new;
        return $this;
    }

    /**
     * @param bool|null $modified
     *
     * @return self
     **/
    public function makeModified($modified=true)
    {
        $this->modified = $modified;
        return $this;
    }
}
