<?php

/**
 *  * Created by mtils on 04.04.20 at 12:18.
 **/

namespace Ems\Model;

use Closure;
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
     * @var Generator
     */
    protected $generator;

    /**
     * @var ClassMap[]
     */
    private $maps;

    /**
     * @var array
     */
    private $mapFactories = [];

    public function __construct(Generator $generator=null)
    {
        $this->generator = $generator ?: new Generator();
    }

    /**
     * Return the storage url.
     *
     * @param string $class
     *
     * @return Url
     */
    public function getStorageUrl(string $class) : Url
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
    public function getStorageName(string $class) : string
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
    public function primaryKey(string $class)
    {
        return $this->getMap($class)->getPrimaryKey();
    }


    /**
     * Return all keys of $class. This includes relations. The relation
     * has to be delivered by getRelation().
     *
     * @param string $class
     *
     * @return string[]
     */
    public function getKeys(string $class) : array
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
    public function getRelationship(string $class, string $name) : Relationship
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
    public function map(string $class, $mapClass) : MapSchemaInspector
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
    public function getMap(string $class) : ClassMap
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
     * @param string $class
     * @return array
     */
    public function getDefaults(string $class): array
    {
        return $this->evaluateValues($this->getMap($class)->getDefaults());
    }

    /**
     * @param string $class
     * @return array
     */
    public function getAutoUpdates(string $class): array
    {
        return $this->evaluateValues($this->getMap($class)->getAutoUpdates());
    }


    /**
     * Use the MapSchemaInspector as a type provider. This works as
     * \Ems\Core\Extractor::extend(new MapSchemaInspector()) or for its primary
     * usage in \Ems\Core\ObjectArrayConverter::setTypeProvider(new MapSchemaInspector()).
     *
     * @param string $class
     * @param string $path
     *
     * @return string|null
     */
    public function type(string $class, string $path) : ?string
    {
        try {

            $map = $this->getMap($class);

            if (strpos($path, '.')) {
                return $this->typeFromNested($map, $path);
            }

            if (in_array($path, $map->getKeys())) {
                return $map->getType($path);
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

    /**
     * Run closure values or take non closure values.
     *
     * @param array $template
     * @return array
     */
    protected function evaluateValues(array $template) : array
    {
        $evaluated = [];
        foreach ($template as $key=>$value) {
            if ($value instanceof Closure) {
                $evaluated[$key] = $value();
                continue;
            }
            $evaluated[$key] = $this->generator->makeOrReturn($value);
        }
        return $evaluated;
    }
}
