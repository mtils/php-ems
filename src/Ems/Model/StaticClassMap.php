<?php

/**
 *  * Created by mtils on 10.04.20 at 12:18.
 **/

namespace Ems\Model;

use Ems\Contracts\Model\Relationship;
use Ems\Core\Exceptions\NotImplementedException;
use Ems\Core\Exceptions\UnConfiguredException;
use Ems\Core\Url;
use ReflectionClass;
use ReflectionMethod;

use ReflectionType;

use function call_user_func;
use function get_class;
use function in_array;

abstract class StaticClassMap extends ClassMap
{
    /**
     * Overwrite this constant to assign the orm class that is described by your
     * your extended map.
     */
    const ORM_CLASS = '';

    /**
     * Overwrite this constant to assign the storage name. This is the table
     * name or a resource endpoint name.
     */
    const STORAGE_NAME = '';

    /**
     * Overwrite this class constant to set the url to storage.
     */
    const STORAGE_URL = '';

    /**
     * @var array
     */
    private static $classConstants = [];

    /**
     * @var array
     */
    private static $methodRelations = [];

    /**
     * @var string[]
     */
    private static $keyCache = [];

    /**
     * @var string[]
     */
    private static $relationReturnTypes = [
        Relationship::class
    ];

    public function getOrmClass()
    {
        if ($this->ormClass) {
            return parent::getOrmClass();
        }

        if (!$class = static::constantValue('ORM_CLASS')) {
            throw new UnConfiguredException('ADD ORM_CLASS class constant to ' . static::class);
        }

        return $class;
    }

    public function getStorageName()
    {
        if ($this->storageName) {
            return parent::getStorageName();
        }

        if (!$storageName = static::constantValue('STORAGE_NAME')) {
            throw new UnConfiguredException('ADD STORAGE_NAME class constant to ' . static::class);
        }

        return $storageName;

    }

    public function getStorageUrl()
    {
        if ($this->storageUrl) {
            return $this->storageUrl;
        }
        if (!$storageUrl = static::constantValue('STORAGE_URL')) {
            throw new UnConfiguredException('ADD STORAGE_URL class constant to ' . static::class);
        }
        return new Url($storageUrl);
    }

    public function getKeys()
    {
        if ($this->keys) {
            return parent::getKeys();
        }

        return static::keys();

    }

    final public function getRelationship($name)
    {
        return static::relation($name);
    }

    public static function keys()
    {
        if (isset(static::$keyCache[static::class])) {
            return static::$keyCache[static::class];
        }
        static::$keyCache[static::class] = [];

        foreach (static::classConstants() as $name => $value) {
            if (static::isKeyConstant($name)) {
                static::$keyCache[static::class][] = $value;
            }
        }
        return static::$keyCache[static::class];
    }

    public static function relation($name)
    {
        $relations = static::methodRelations();
        if (isset($relations[$name])) {
            /** @var Relationship $relation */
            $relation = call_user_func([static::class, $name]);
            return $relation->name($name);
        }

        throw new NotImplementedException("No matching method for relation named '$name' found in " . static::class);
    }

    /**
     * @param string $type
     */
    public static function addRelationReturnType($type)
    {
        static::$relationReturnTypes[] = $type;
    }

    /**
     * @param object $parent (optional)
     *
     * @return Relation
     */
    protected static function newRelation($parent=null)
    {
        if (!$parent) {
            $parentClass = static::ORM_CLASS;
            $parent = new $parentClass();
        }
        return (new Relation())->setParent($parent);
    }

    /**
     * Create a new relationship and return it.
     *
     * @param string|object $related
     * @param string $relatedKey (optional)
     * @param string $ownerKey (optional)
     *
     * @return Relationship
     */
    protected static function relateTo($related, $relatedKey='', $ownerKey='')
    {
        return (new Relationship())
            ->relateTo($related, $relatedKey)
            ->owner(static::ORM_CLASS, $ownerKey);
    }

    /**
     * @param string $name
     *
     * @return string|null
     *
     * @throws \ReflectionException
     */
    protected static function constantValue($name)
    {
        $constants = static::classConstants();
        return isset($constants[$name]) ? $constants[$name] : null;
    }

    /**
     * @return array
     *
     * @throws \ReflectionException
     */
    protected static function classConstants()
    {
        $class = static::class;

        if (isset(static::$classConstants[$class])) {
            return static::$classConstants[$class];
        }

        $reflection = new ReflectionClass($class);
        static::$classConstants[$class] = [];

        static::$classConstants[$class] = $reflection->getConstants();

//        foreach ($reflection->getConstants() as $constant) {
//            static::$classConstants[$constant] = $constant;
//        }

        return static::$classConstants[$class];
    }

    protected static function methodRelations()
    {
        $class = static::class;
        if (isset(static::$methodRelations[$class])) {
            return static::$methodRelations[$class];
        }
        static::$methodRelations[$class] = [];

        $reflection = new ReflectionClass($class);
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_STATIC);

        foreach ($methods as $method) {
            $type = $method->getReturnType();
            $typeName = $type instanceof ReflectionType ? $type->getName() : '';
            if (in_array($typeName, static::$relationReturnTypes)) {
                static::$methodRelations[$class][$method->name] = true;
            }
        }
        return static::$methodRelations[$class];
    }

    protected static function isKeyConstant($constantName)
    {
        return !static::isReservedConstant($constantName) && !static::isRelation($constantName);
    }

    protected static function isReservedConstant($key)
    {
        return in_array($key, ['STORAGE_NAME', 'ORM_CLASS', 'STORAGE_URL']);
    }

    protected static function isRelation($key)
    {
        $relations = static::methodRelations();
        return isset($relations[$key]);
    }
}