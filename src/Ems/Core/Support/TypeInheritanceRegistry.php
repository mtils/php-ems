<?php
/**
 *  * Created by mtils on 10.02.18 at 07:16.
 **/

namespace Ems\Core\Support;


use ArrayAccess;
use Countable;
use Ems\Contracts\Core\Provider;
use Ems\Core\Exceptions\HandlerNotFoundException;

class TypeInheritanceRegistry implements Provider, ArrayAccess, Countable
{
    /**
     * @var array
     */
    protected $types = [];

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

    }

    /**
     * {@inheritdoc}
     *
     * @param string $type
     *
     * @throws \Ems\Contracts\Core\Errors\NotFound
     *
     * @return mixed
     **/
    public function getOrFail($type)
    {
        if ($handler = $this->get($type)) {
            return $handler;
        }

        throw new HandlerNotFoundException("No handler for type $type found.");
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
        return isset($this->types[$offset]);
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
        return $this->types[$offset];
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
        $this->types[$offset] = $value;
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
        unset($this->types[$offset]);
    }

    /**
     * @return int
     */
    #[\ReturnTypeWillChange]
    public function count()
    {
        return count($this->types);
    }

}