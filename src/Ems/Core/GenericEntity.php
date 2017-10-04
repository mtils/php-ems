<?php

namespace Ems\Core;

use Ems\Contracts\Core\Entity;
use Ems\Contracts\Core\AppliesToResource;

class GenericEntity extends NamedObject implements Entity
{
    /**
     * @var bool
     **/
    protected $isNew = true;

    /**
     * @var bool
     **/
    protected $modified = false;

    /**
     * {@inheritdoc}
     *
     * @return bool
     **/
    public function isNew()
    {
        return $this->isNew;
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
        return $this->isDirty($attributes);
    }

    /**
     * @param bool $new (default:true)
     *
     * @return self
     **/
    public function makeNew($new=true)
    {
        $this->isNew = $new;
        return $this;
    }

    /**
     * @param bool $modified
     *
     * @return self
     **/
    public function makeModified($modified=true)
    {
        $this->modified = $modified;
        return $this;
    }
}
