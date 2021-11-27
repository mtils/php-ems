<?php

/**
 *  * Created by mtils on 04.04.20 at 12:28.
 **/

namespace Ems\Model;

use Closure;
use Ems\Contracts\Core\Url as UrlContract;
use Ems\Contracts\Model\Relationship;
use Ems\Core\Url;

use function is_array;

/**
 * Class ClassMap
 *
 * This class tells MapSchemaInspector information how to work with your orm
 * objects.
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
     * @var string|string[]
     */
    protected $primaryKey = 'id';

    /**
     * @var array
     */
    protected $keys = [];

    /**
     * @var array
     */
    protected $relations = [];

    /**
     * @var array
     */
    protected $types = [];

    /**
     * @var array
     */
    protected $defaults = [];

    /**
     * @var array
     */
    protected $autoUpdates = [];

    /**
     * Get the url of the connection to the object. This could be a hard coded
     * connection url like mysql://user:password@host/database (not so good) or
     * some artificial internal app url like database://default.
     *
     * @return UrlContract
     */
    public function getStorageUrl()
    {
        return $this->storageUrl;
    }

    /**
     * @param UrlContract|string $url
     *
     * @return $this
     *
     * @see self::getStorageUrl()
     */
    public function setStorageUrl($url)
    {
        $this->storageUrl = $url instanceof UrlContract ? $url : new Url($url);
        return $this;
    }

    /**
     * Get the class of the orm object.
     *
     * @return string
     */
    public function getOrmClass()
    {
        return $this->ormClass;
    }

    /**
     * Set the orm class.
     *
     * @param string $class
     *
     * @return $this
     */
    public function setOrmClass($class)
    {
        $this->ormClass = $class;
        return $this;
    }

    /**
     * Get the name inside storage. This could be a table name or a rest endpoint.
     *
     * @return string
     */
    public function getStorageName()
    {
        return $this->storageName;
    }

    /**
     * @param string $name
     *
     * @return $this
     *
     * @see self::getStorageName()
     */
    public function setStorageName($name)
    {
        $this->storageName = $name;
        return $this;
    }

    /**
     * @return string|string[]
     */
    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    /**
     * @param string|string[] $primaryKey
     * @return ClassMap
     */
    public function setPrimaryKey($primaryKey)
    {
        $this->primaryKey = $primaryKey;
        return $this;
    }

    /**
     * Get the keys (properties,columns) of the object.
     *
     * @return array
     */
    public function getKeys()
    {
        return $this->keys;
    }

    /**
     * Set the keys (properties,columns) of the object.
     *
     * @param array $keys
     *
     * @return $this
     */
    public function setKeys(array $keys)
    {
        $this->keys = $keys;
        return $this;
    }

    /**
     * Get the $name relation.
     *
     * @param string $name
     *
     * @return Relationship
     */
    public function getRelationship($name)
    {
        return $this->relations[$name] ?? null;
    }

    /**
     * Set the relation for $name.
     *
     * @param string       $name
     * @param Relationship $relation
     *
     * @return $this
     */
    public function setRelationship($name, Relationship $relation)
    {
        $this->relations[$name] = $relation;
        return $this;
    }

    /**
     * @return array
     */
    public function getDefaults() : array
    {
        return $this->defaults;
    }

    /**
     * Set the default values of this class. Set a Closure as value to get
     * real time evaluated values. (Like Timestamps etc.)
     *
     * @param array $defaults
     * @return $this
     */
    public function setDefaults(array $defaults) : ClassMap
    {
        $this->defaults = $defaults;
        return $this;
    }

    /**
     * @return array
     */
    public function getAutoUpdates() : array
    {
        return $this->autoUpdates;
    }

    /**
     * Set the auto updated values. Set closures for real time evaulated values.
     *
     * @param array $autoUpdates
     * @return $this
     */
    public function setAutoUpdates(array $autoUpdates) : ClassMap
    {
        $this->autoUpdates = $autoUpdates;
        return $this;
    }

    /**
     * @param $key
     * @return string
     */
    public function getType($key) : string
    {
        return $this->types[$key] ?? 'string';
    }

    /**
     * Set the type for $key or set all types.
     *
     * @param string|array $key
     * @param $value
     * @return $this
     */
    public function setType($key, $value=null) : ClassMap
    {
        if (is_array($key)) {
            $this->types = $key;
            return $this;
        };
        $this->types[$key] = $value;
        return $this;
    }

}
