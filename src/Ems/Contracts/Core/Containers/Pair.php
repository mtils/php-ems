<?php
/**
 *  * Created by mtils on 16.12.17 at 20:36.
 **/

namespace Ems\Contracts\Core\Containers;


use ArrayAccess;
use Ems\Core\Support\TypeCheckMethods;
use OutOfBoundsException;

/**
 * Class Pair
 *
 * A pair is a container for exactly two values of the same type.
 * You could compare pairs or do stuff like pseudo-operators with them.
 * Possible inherited classes of pair are: Size(float, float), Point(float, float),
 * Line(Point, Point) and so on.
 *
 * @package Ems\Contracts\Core\Containers
 */
class Pair implements ArrayAccess
{
    use TypeCheckMethods;

    /**
     * @var mixed
     */
    protected $first;

    /**
     * @var mixed
     */
    protected $second;

    /**
     * Change this to a class or interface name
     *
     * @var string
     **/
    protected $forceType = '';

    /**
     * @var bool
     **/
    protected $typeIsFrozen = false;

    /**
     * Pair constructor.
     *
     * @param Pair|mixed $first  (optional)
     * @param mixed      $second (optional)
     * @param string     $type   (optional)
     */
    public function __construct($first=null, $second=null, $type=null)
    {
        if ($first instanceof Pair) {
            $second = $first->second();
            $type = $type ? $type : $first->getForcedType();
            $first = $first->first();
        }

        $this->first = $first;
        $this->second = $second;
        if ($type) {
            $this->setForcedType($type);
            $this->freezeType();
        }
    }

    /**
     * @return mixed
     */
    public function first()
    {
        return $this->first;
    }

    /**
     * @param mixed $first
     *
     * @return $this
     */
    public function setFirst($first)
    {
        $this->first = $this->checkType($first);
        return $this;
    }

    /**
     * @return mixed
     */
    public function second()
    {
        return $this->second;
    }

    /**
     * @param mixed $second
     *
     * @return $this
     */
    public function setSecond($second)
    {
        $this->second = $this->checkType($second);
        return $this;
    }

    /**
     * @return mixed
     */
    public function last()
    {
        return $this->second;
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->first) && empty($this->second);
    }

    /**
     * @return bool
     */
    public function isNull()
    {
        return $this->first === null && $this->second === null;
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        return $this->hasAllowedType($this->first) && $this->hasAllowedType($this->second);
    }

    /**
     * Return a new copy of this pair.
     *
     * @return static
     */
    public function copy()
    {
        return new static($this->first, $this->second, $this->forceType);
    }

    /**
     * Swaps first and second.
     *
     * @return $this
     */
    public function swap()
    {
        $first = $this->first;
        $this->first = $this->second;
        $this->second = $first;
        return $this;
    }

    /**
     * @return static
     */
    public function swapped()
    {
        return new static($this->second, $this->first, $this->forceType);
    }

    /**
     * Return the sum (or in inherited product) of the values.
     *
     * @return mixed
     */
    public function total()
    {
        return $this->first + $this->second;
    }

    /**
     * @param Pair $pair
     *
     * @return bool
     */
    public function equals(Pair $pair)
    {
        return $pair->first() == $this->first() && $pair->second() == $this->second();
    }

    /**
     * @param Pair $pair
     *
     * @return bool
     */
    public function isGreaterThan(Pair $pair)
    {
        return $this->total() > $pair->total();
    }

    /**
     * @param Pair $pair
     *
     * @return bool
     */
    public function isGreaterOrEqual(Pair $pair)
    {
        return $this->total() >= $pair->total();
    }

    /**
     * @param Pair $pair
     *
     * @return bool
     */
    public function isLessThan(Pair $pair)
    {
        return $this->total() < $pair->total();
    }

    /**
     * @param Pair $pair
     *
     * @return bool
     */
    public function isLessOrEqual(Pair $pair)
    {
        return $this->total() <= $pair->total();
    }

    /**
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     * @since 5.0.0
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return ($offset == 1) || ($offset == 2);
    }

    /**
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     * @since 5.0.0
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->{$this->property($offset)};
    }

    /**
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     * @since 5.0.0
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        $this->{$this->property($offset)} = $this->checkType($value);
    }

    /**
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     * @since 5.0.0
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        $this->{$this->property($offset)} = null;
    }

    /**
     * @param mixed $offset
     *
     * @return string
     */
    protected function property($offset)
    {
        if ($offset == 1) {
            return 'first';
        }

        if ($offset == 2) {
            return 'second';
        }

        throw new OutOfBoundsException('Index has to be 1 or 2');
    }

}