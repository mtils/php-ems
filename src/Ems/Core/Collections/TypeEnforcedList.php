<?php

namespace Ems\Core\Collections;

use InvalidArgumentException;
use BadMethodCallException;
use Ems\Core\Support\TypeCheckMethods;

class TypeEnforcedList extends OrderedList
{
    use TypeCheckMethods;

    /**
     * @var string
     **/
    protected $forceType = 'string';

    /**
     * @var bool
     **/
    protected $typeIsFrozen = false;

    /**
     * Insert a value at position $index.
     *
     * @param int   $index
     * @param mixed $value
     *
     * @return self
     **/
    public function insert($index, $value)
    {
        $this->checkType($value);

        return parent::insert($index, $value);
    }

    /**
     * {@inheritdoc}
     *
     * @param array|\Traversable|int|string $source (optional)
     *
     * @return self
     **/
    public function setSource($source)
    {
        $array = $this->castToArray($source);

        foreach ($array as $item) {
            $this->append($item);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     * Copies the type but not the freeze.
     *
     * @return self
     */
    public function copy()
    {
        return (new static($this->source))->setForcedType($this->forceType);
    }

    /**
     * Append a value to the end of this list.
     *
     * @param mixed $value
     *
     * @return self
     **/
    protected function addItem($value)
    {
        $this->checkType($value);

        return parent::addItem($value);
    }
}
