<?php

/**
 *  * Created by mtils on 10.04.20 at 12:18.
 **/

namespace Ems\Model;

use Ems\Core\Exceptions\NotImplementedException;
use Ems\Core\Exceptions\UnConfiguredException;
use Ems\Core\Url;
use ReflectionClass;
use ReflectionMethod;

use function call_user_func;
use function in_array;

abstract class StaticClassMap extends ClassMap
{
    /**
     * @var array
     */
    private static $classConstants;

    /**
     * @var array
     */
    private static $methodRelations;

    /**
     * @var string[]
     */
    private static $keyCache;

    public function getOrmClass()
    {
        if ($this->ormClass) {
            return parent::getOrmClass();
        }

        if (!$class = self::constantValue('ORM_CLASS')) {
            throw new UnConfiguredException('ADD ORM_CLASS class constant to ' . static::class);
        }

        return $class;
    }

    public function getStorageName()
    {
        if ($this->storageName) {
            return parent::getStorageName();
        }

        if (!$storageName = self::constantValue('STORAGE_NAME')) {
            throw new UnConfiguredException('ADD STORAGE_NAME class constant to ' . static::class);
        }

        return $storageName;

    }

    public function getStorageUrl()
    {
        if ($this->storageUrl) {
            return $this->storageUrl;
        }
        if (!$storageUrl = self::constantValue('STORAGE_URL')) {
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

    final public function getRelation($name)
    {
        return static::relation($name);
    }

    public static function keys()
    {
        if (static::$keyCache !== null) {
            return static::$keyCache;
        }
        static::$keyCache = [];
        foreach (self::classConstants() as $name => $value) {
            if (static::isKeyConstant($name)) {
                static::$keyCache[] = $value;
            }
        }
        return static::$keyCache;
    }

    public static function relation($name)
    {
        $relations = static::methodRelations();
        if (isset($relations[$name])) {
            return call_user_func([static::class, $name]);
        }
        throw new NotImplementedException("No matching method for relation named '$name' found");
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
        $constants = self::classConstants();
        return isset($constants[$name]) ? $constants[$name] : null;
    }

    /**
     * @return array
     *
     * @throws \ReflectionException
     */
    protected static function classConstants()
    {
        if (static::$classConstants !== null) {
            return static::$classConstants;
        }

        $reflection = new ReflectionClass(static::class);
        static::$classConstants = [];

        static::$classConstants = $reflection->getConstants();

//        foreach ($reflection->getConstants() as $constant) {
//            static::$classConstants[$constant] = $constant;
//        }

        return static::$classConstants;
    }

    protected static function methodRelations()
    {
        if (static::$methodRelations !== null) {
            return static::$methodRelations;
        }
        static::$methodRelations = [];

        $reflection = new ReflectionClass(static::class);
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_STATIC);

        foreach ($methods as $method) {
            if ($method->getReturnType() == \Ems\Contracts\Model\Relation::class) {
                static::$methodRelations[$method->name] = true;
            }
        }
        return static::$methodRelations;
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