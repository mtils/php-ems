<?php

/**
 *  * Created by mtils on 10.04.20 at 12:18.
 **/

namespace Ems\Model;

use DateTime;
use Ems\Contracts\Model\Relationship;
use Ems\Core\Exceptions\NotImplementedException;
use Ems\Core\Exceptions\UnConfiguredException;
use Ems\Core\Url;
use ReflectionClass;
use ReflectionClassConstant;
use ReflectionException;
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
     * Overwrite this class constant with your primary key(s)
     */
    const PRIMARY_KEY = 'id';

    /**
     * Overwrite this constant to set an array of types.
     */
    const TYPES = [];

    /**
     * Overwrite this to set defaults for new objects.
     */
    const DEFAULTS = [];

    /**
     * Overwrite this to generate update values ([updated_at => self::NOW])
     */
    const ON_UPDATE = [];

    /**
     * Use this constant
     */
    const NOW = 'NOW()';

    /**
     * @var string|string[]
     */
    protected $primaryKey = null;

    /**
     * @var array
     */
    protected static $classConstants = [];

    /**
     * @var array
     */
    protected static $methodRelations = [];

    /**
     * @var string[]
     */
    protected static $keyCache = [];

    /**
     * @var string[]
     */
    protected static $relationReturnTypes = [
        Relationship::class
    ];

    /**
     * @var array
     */
    protected static $ownConstants = [];

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

    /**
     * @return string|string[]
     */
    public function getPrimaryKey()
    {
        if (!$this->primaryKey) {
            $pk = static::constantValue('PRIMARY_KEY');
            $this->primaryKey = $pk ?: 'id';
        }
        return parent::getPrimaryKey();
    }


    public function getKeys()
    {
        if ($this->keys) {
            return parent::getKeys();
        }

        return static::keys();

    }

    public function getType($key): string
    {
        if (isset($this->types[$key])) {
            return parent::getType($key);
        }
        if (!$types = static::constantValue('TYPES')) {
            return parent::getType($key);
        }
        return $types[$key] ?? parent::getType($key);
    }

    public function getDefaults(): array
    {
        if ($this->defaults) {
            return parent::getDefaults();
        }
        if (!$defaults = static::constantValue('DEFAULTS')) {
            return parent::getDefaults();
        }
        return $this->evaluateValues($defaults);
    }

    public function getAutoUpdates(): array
    {
        if ($this->autoUpdates) {
            return parent::getAutoUpdates();
        }
        if (!$updates = static::constantValue('ON_UPDATE')) {
            return parent::getAutoUpdates();
        }
        return $this->evaluateValues($updates);
    }

    protected function evaluateValues(array $template): array
    {
        foreach ($template as $key=>$value) {
            if ($value === self::NOW) {
                $template[$key] = function () { return new DateTime();};
            }
        }
        return parent::evaluateValues($template);
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
     * @return string|array|null
     */
    protected static function constantValue($name)
    {
        $constants = static::classConstants();
        return $constants[$name] ?? null;
    }

    /**
     * @return array
     */
    protected static function classConstants()
    {
        $class = static::class;

        if (isset(static::$classConstants[$class])) {
            return static::$classConstants[$class];
        }

        static::$classConstants[$class] = [];

        try {
            $reflection = new ReflectionClass($class);
            static::$classConstants[$class] = $reflection->getConstants();
        } catch (ReflectionException $e) {
            //
        }

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


    protected static function isReservedConstant($key) : bool
    {
        return in_array($key, self::getOwnConstants());
    }

    protected static function getOwnConstants()
    {
        $class = new ReflectionClass(self::class);
        if (self::$ownConstants) {
            return self::$ownConstants;
        }
        foreach ($class->getConstants() as $constant=>$value) {
            self::$ownConstants[] = $constant;
        }
        return self::$ownConstants;
    }

    protected static function isRelation($key) : bool
    {
        $relations = static::methodRelations();
        return isset($relations[$key]);
    }
}