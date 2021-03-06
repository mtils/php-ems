<?php

/**
 *  * Created by mtils on 04.04.20 at 12:18.
 **/

namespace Ems\Model;

use Ems\Contracts\Core\Exceptions\TypeException;
use Ems\Contracts\Core\Url;
use Ems\Contracts\Model\Relationship;
use Ems\Contracts\Model\SchemaInspector;
use Ems\Core\Exceptions\HandlerNotFoundException;
use Ems\Core\Exceptions\NotImplementedException;

use function array_pop;
use function call_user_func;
use function explode;
use function get_class;
use function in_array;
use function is_callable;
use function is_string;
use function strpos;

/**
 * Class MapSchemaInspector
 *
 * @package Ems\Model
 */
class MapSchemaInspector implements SchemaInspector
{
    /**
     * @var ClassMap[]
     */
    private $maps;

    /**
     * @var array
     */
    private $mapFactories = [];

    /**
     * Return the storage url.
     *
     * @param string $class
     *
     * @return Url
     */
    public function getStorageUrl($class)
    {
        return $this->getMap($class)->getStorageUrl();
    }

    /**
     * Return the name of $class in storage. In ORM/Database objects
     * this would be the table. In a REST API this would be properties
     * of the name of the endpoint name.
     *
     * @param string $class
     *
     * @return string
     */
    public function getStorageName($class)
    {
        return $this->getMap($class)->getStorageName();
    }

    /**
     * {@inheritDoc}
     *
     * @param string $class
     *
     * @return string|string[]
     */
    public function primaryKey($class)
    {
        return $this->getMap($class)->getPrimaryKey();
    }


    /**
     * Return all keys of $class. This includes relations. The relation
     * has to be delivered by getRelation().
     *
     * @param $class
     *
     * @return string[]
     */
    public function getKeys($class)
    {
        return $this->getMap($class)->getKeys();
    }

    /**
     * Return the relation object that describes the relation to a foreign
     * object. The other object does not have to be in the same storage.
     *
     * @param string $class
     * @param string $name
     *
     * @return Relationship
     */
    public function getRelationship($class, $name)
    {
        return $this->getMap($class)->getRelationship($name);
    }

    /**
     * Map a class to a ClassMap. Better use class names
     * or Closures to omit a complete load of the
     * Schema.
     *
     * @param string                   $class
     * @param string|callable|ClassMap $mapClass
     *
     * @return $this
     */
    public function map($class, $mapClass)
    {
        if ($mapClass instanceof ClassMap) {
            $this->maps[$class] = $mapClass;
            if (!$mapClass->getOrmClass()) {
                $mapClass->setOrmClass($class);
            }
            return $this;
        }

        if (is_callable($mapClass)) {
            $this->mapFactories[$class] = $mapClass;
            return $this;
        }

        if (!is_string($mapClass)) {
            throw new TypeException('$mapClass has to be ClassMap, callable or string');
        }

        $this->mapFactories[$class] = function () use ($mapClass) {
            return new $mapClass();
        };

        return $this;
    }

    /**
     * @param string $class
     *
     * @return ClassMap
     *
     * @throws HandlerNotFoundException
     */
    public function getMap($class)
    {
        if (isset($this->maps[$class])) {
            return $this->maps[$class];
        }
        if (!isset($this->mapFactories[$class])) {
            throw new HandlerNotFoundException("No map or factory found for $class");
        }
        $this->maps[$class] = call_user_func($this->mapFactories[$class]);
        return $this->getMap($class);
    }

    /**
     * Use the MapSchemaInspector as a type provider. This works as
     * \Ems\Core\Extractor::extend(new MapSchemaInspector()) or for its primary
     * usage in \Ems\Core\ObjectArrayConverter::setTypeProvider(new MapSchemaInspector()).
     *
     * @param string $class
     * @param string $path
     *
     * @return string
     */
    public function type(string $class, string $path)
    {
        try {

            $map = $this->getMap($class);

            if (strpos($path, '.')) {
                return $this->typeFromNested($map, $path);
            }

            $keys = $map->getKeys();

            if (in_array($path, $keys)) {
                return 'string';
            }

            if (!$relation = $map->getRelationship($path)) {
                return null;
            }
            $type = get_class($relation->related);
            return $relation->hasMany ? $type.'[]' : $type;

        } catch (HandlerNotFoundException $e) {
            return null;
        } catch (NotImplementedException $e) {
            return null;
        }
    }

    /**
     * @param ClassMap $map
     * @param string $path
     * @return string|null
     */
    protected function typeFromNested(ClassMap $map, string $path)
    {
        $parentClass = $map->getOrmClass();
        $parts = explode('.', $path);
        $last = array_pop($parts);

        foreach ($parts as $segment) {
            $relation = $this->getRelationship($parentClass, $segment);
            $parentClass = get_class($relation->related);
        }

        return $this->type($parentClass, $last);
    }
}
