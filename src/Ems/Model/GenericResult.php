<?php

namespace Ems\Model;

use Ems\Contracts\Core\Type;
use Ems\Contracts\Model\Result;
use ArrayIterator;
use function is_callable;
use Iterator;
use Traversable;
use UnexpectedValueException;

class GenericResult implements Result
{
    use ResultTrait;

    /**
     * @var \Traversable|array
     */
    protected $data;

    /**
     * @var callable
     **/
    protected $getter;

    /**
     * Pass a callable which will return the complete result. Or if you already
     * have the complete result pass the data.
     *
     * @param callable|Traversable|array $getter
     * @param object                     $creator (optional)
     **/
    public function __construct($getter, $creator = null)
    {
        if (is_callable($getter)) {
            $this->setGetter($getter);
        }

        if (!$this->getter) {
            $this->setData($getter);
        }

        $this->_creator = $creator ? $creator : $this->_creator;
    }

    /**
     * Return an iterator to traverse over the result.
     *
     * @return \Iterator
     **/
    public function getIterator()
    {

        $result = $this->data !== null ? $this->data : call_user_func($this->getter);

        if (!$result) {
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
     * @return array|Traversable
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param \Traversable|array $data
     *
     * @return $this
     */
    public function setData($data)
    {
        $this->data = Type::forceAndReturn($data, Traversable::class);
        return $this;
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
            $this->_creator = $getter[0];
        }

        return $this;
    }
}
