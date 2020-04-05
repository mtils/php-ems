<?php

/**
 *  * Created by mtils on 04.04.20 at 12:28.
 **/

namespace Ems\Model;

use Ems\Contracts\Core\Url;
use Ems\Contracts\Model\Relation as RelationContract;

/**
 * Class ClassMap
 *
 * @package Ems\Model
 */
class ClassMap
{
    /**
     * @var Url
     */
    protected $storageUrl;

    /**
     * @var string
     */
    protected $storageName = '';

    /**
     * @var string
     */
    protected $ormClass = '';

    /**
     * @var array
     */
    protected $keys = [];

    /**
     * @var array
     */
    protected $relations = [];

    public function getStorageUrl()
    {
        return $this->storageUrl;
    }

    public function setStorageUrl($url)
    {
        $this->storageUrl = $url;
        return $this;
    }

    public function getOrmClass()
    {
        return $this->ormClass;
    }

    public function setOrmClass($class)
    {
        $this->ormClass = $class;
        return $this;
    }

    public function getStorageName()
    {
        return $this->storageName;
    }

    public function setStorageName($name)
    {
        $this->storageName = $name;
        return $this;
    }

    public function getKeys()
    {
        return $this->keys;
    }

    public function setKeys(array $keys)
    {
        $this->keys = $keys;
        return $this;
    }

    public function getRelation($name)
    {
        return $this->relations[$name];
    }

    public function setRelation($name, RelationContract $relation)
    {
        $this->relations[$name] = $relation;
        return $this;
    }
}