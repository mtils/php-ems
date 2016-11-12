<?php

namespace Ems\Model;

use Ems\Contracts\Model\Result;
use ArrayIterator;
use Iterator;
use Traversable;
use UnexpectedValueException;

class GenericResult implements Result
{
    /**
     * @var callable
     **/
    protected $getter;

    /**
     * @var object
     **/
    protected $creator;

    /**
     * Pass a callable which will return the complete result.
     *
     * @param callable $getter
     * @param object   $creator (optional)
     **/
    public function __construct(callable $getter, $creator = null)
    {
        $this->setGetter($getter);
        $this->creator = $creator ? $creator : $this->creator;
    }

    /**
     * {@inheritdoc}
     *
     * @return object
     **/
    public function creator()
    {
        return $this->creator;
    }

    /**
     * Return an iterator to traverse over the result.
     *
     * @return \Iterator
     **/
    public function getIterator()
    {
        if (!$result = call_user_func($this->getter)) {
            return new ArrayIterator([]);
        }

        if ($result instanceof Iterator) {
            return $result;
        }

        if (is_array($result)) {
            return new ArrayIterator($result);
        }

        if (!$result instanceof Traversable) {
            throw new UnexpectedValueException('The returned result of the assigned callable is not supported (traversable)');
        }

        $array = [];

        foreach ($result as $item) {
            $array[] = $item;
        }

        return new ArrayIterator($array);
    }

    /**
     * Set the getter to retrieve the results.
     *
     * @param callable $getter
     *
     * @return self
     **/
    protected function setGetter(callable $getter)
    {
        $this->getter = $getter;

        if (is_array($getter) && is_object($getter[0])) {
            $this->creator = $getter[0];
        }

        return $this;
    }
}
