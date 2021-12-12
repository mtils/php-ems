<?php
/**
 *  * Created by mtils on 08.12.2021 at 20:40.
 **/

namespace Ems\Http;

use Ems\Contracts\Core\Input as InputContract;
use Ems\Core\Collections\OrderedList;
use Ems\Core\Input;

use function array_key_exists;
use function array_unique;

class HttpInput extends Input
{
    protected $gpcOrder = [
        InputContract::POOL_GET, InputContract::POOL_POST, InputContract::POOL_COOKIE, InputContract::POOL_SERVER
    ];

    /**
     * @var array[]
     */
    protected $request = [
        InputContract::POOL_GET => [],
        InputContract::POOL_POST => [],
        InputContract::POOL_COOKIE => [],
        InputContract::POOL_SERVER => [],
        InputContract::POOL_FILES => []
    ];

    public function __construct(array $get=[], array $post=[], array $cookies=[], array $files=[], array $server=[], array $attributes=[])
    {
        parent::__construct($attributes);
        $this->init($get, $post, $cookies, $files, $server);
    }

    /**
     * {@inheritDoc}
     *
     * @param $key
     * @param $default
     * @return mixed|null
     */
    public function get($key, $default = null)
    {
        if (array_key_exists($key, $this->_attributes)) {
            return $this->_attributes[$key];
        }
        $result = $this->offsetGet($key);
        return $result === null ? $default : $result;
    }

    /**
     * Check if parameter was set (manually or by the request).
     *
     * @param $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        if (isset($this->_attributes[$offset])) {
            return true;
        }
        foreach ($this->gpcOrder as $source) {
            if (isset($this->request[$source][$offset])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get value from any source.
     *
     * @param $offset
     * @return mixed|null
     */
    public function offsetGet($offset)
    {
        if (isset($this->_attributes[$offset])) {
            return $this->_attributes[$offset];
        }
        foreach ($this->gpcOrder as $source) {
            if (isset($this->request[$source][$offset])) {
                return $this->request[$source][$offset];
            }
        }
        return null;
    }

    /**
     * Unset $offset from any source.
     *
     * @param $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        if (isset($this->_attributes[$offset])) {
            unset($this->_attributes[$offset]);
        }
        foreach ($this->gpcOrder as $source) {
            if (isset($this->request[$source][$offset])) {
                unset($this->request[$source][$offset]);
            }
        }
    }

    /**
     * @return OrderedList
     */
    public function keys()
    {
        $keys = array_keys($this->_attributes);
        foreach ($this->gpcOrder as $source) {
            foreach ($this->request[$source] as $key=>$value) {
                $keys[] = $key;
            }
        }
        return new OrderedList(array_unique($keys));
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $array = [];
        foreach ($this->keys() as $key) {
            $array[$key] = $this->offsetGet($key);
        }
        return $array;
    }

    public function only($pool)
    {

    }

    /**
     * @return string[]
     */
    public function getGpcOrder(): array
    {
        return $this->gpcOrder;
    }

    /**
     * @param string[] $gpcOrder
     */
    public function setGpcOrder(array $gpcOrder) : HttpInput
    {
        $this->gpcOrder = $gpcOrder;
        return $this;
    }

    protected function init(array $get=[], array $post=[], array $cookies=[], array $files=[], array $server=[])
    {
        $this->request[InputContract::POOL_GET] = $get;
        $this->request[InputContract::POOL_POST] = $post;
        $this->request[InputContract::POOL_COOKIE] = $cookies;
        $this->request[InputContract::POOL_SERVER] = $server;
        $this->request[InputContract::POOL_FILES] = $files;
    }

}