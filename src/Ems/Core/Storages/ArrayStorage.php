<?php
/**
 *  * Created by mtils on 24.03.19 at 10:54.
 **/

namespace Ems\Core\Storages;


use Ems\Contracts\Core\HasKeys;
use Ems\Contracts\Core\Storage;
use Ems\Core\Support\FastArrayDataTrait;

class ArrayStorage implements Storage, HasKeys
{
    use FastArrayDataTrait;

    /**
     * ArrayStorage constructor.
     *
     * @param array $data
     */
    public function __construct($data=[])
    {
        $this->_attributes = $data;
    }

    /**
     * @return string
     */
    public function storageType()
    {
        return Storage::MEMORY;
    }

    /**
     * @param array|null $keys
     * @return bool
     */
    public function purge(array $keys = null)
    {
        $this->clear($keys);
        return true;
    }

    /**
     * @return bool
     */
    public function persist()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function isBuffered()
    {
        return false;
    }

}