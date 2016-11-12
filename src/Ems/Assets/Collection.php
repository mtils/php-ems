<?php

namespace Ems\Assets;

use Ems\Contracts\Assets\Collection as CollectionContract;
use Ems\Core\Collections\TypeEnforcedList;
use Ems\Contracts\Assets\Asset as AssetContract;
use Ems\Core\Support\RenderableTrait;
use InvalidArgumentException;
use Exception;

class Collection extends TypeEnforcedList implements CollectionContract
{
    use RenderableTrait;

    /**
     * @var string
     **/
    protected $forceType = 'Ems\Contracts\Assets\Asset';

    /**
     * @var string
     **/
    protected $mimeType = '';

    /**
     * @var bool
     **/
    protected $typeIsFrozen = true;

    /**
     * @var string
     **/
    protected $group = '';

    /**
     * {@inheritdoc}
     *
     * @return string
     **/
    public function group()
    {
        return $this->group;
    }

    /**
     * Set the group of this collection.
     *
     * @param string $group
     *
     * @return self
     **/
    public function setGroup($group)
    {
        $this->group = $group;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     **/
    public function mimeType()
    {
        return $this->mimeType;
    }

    /**
     * Checks the type of any added item and throws an exception
     * if it does not match.
     *
     * @param mixed $value
     **/
    protected function checkType($value)
    {
        if (!$value instanceof AssetContract) {
            throw new InvalidArgumentException("You can only add values of '{$this->forceType}' to this list");
        }

        if (!$mimeType = $value->mimeType()) {
            throw new InvalidArgumentException('The added asset must have a mimeType to add it to this collection');
        }

        if (!$this->mimeType) {
            $this->mimeType = $mimeType;

            return;
        }

        if ($this->mimeType != $mimeType) {
            throw new InvalidArgumentException("This collection accepts only mimeType {$this->mimeType} not $mimeType");
        }
    }
}
