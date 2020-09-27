<?php
/**
 *  * Created by mtils on 30.08.20 at 06:18.
 **/

namespace Ems\Core;

use Ems\Contracts\Core\Containers\ByTypeContainer;
use Ems\Contracts\Core\ListAdapter;
use Ems\Contracts\Core\ObjectArrayConverter as ObjectArrayConverterContract;
use Ems\Contracts\Core\Type;
use Ems\Core\Exceptions\HandlerNotFoundException;
use stdClass;
use Traversable;

use function array_search;
use function call_user_func;
use function get_class;
use function get_object_vars;
use function gettype;
use function interface_exists;
use function is_array;
use function is_object;
use function substr;

class ObjectArrayConverter implements ObjectArrayConverterContract, ListAdapter
{
    /**
     * @var callable
     */
    protected $typeProvider;

    /**
     * @var ByTypeContainer
     */
    protected $handlers;

    /**
     * @var ByTypeContainer
     */
    protected $listAdapters;

    public function __construct()
    {
        $this->handlers = new ByTypeContainer();
        $this->listAdapters = new ByTypeContainer();
    }

    /**
     * {@inheritDoc}
     *
     * @param object $object
     * @param int $depth (default:0)
     *
     * @return array
     */
    public function toArray($object, $depth = 0)
    {
        $class = get_class($object);
        /** @var ObjectArrayConverterContract $converter */
        if ($converter = $this->handlers->forInstanceOf($class)) {
            return $converter->toArray($object, $depth);
        }
        return $this->ensureArray(get_object_vars($object), $depth);
    }

    /**
     * {@inheritDoc}
     *
     * @param string    $classOrInterface
     * @param array     $data (optional)
     * @param bool      $isFromStorage (default:false)
     *
     * @return object
     */
    public function fromArray(string $classOrInterface, array $data=[], $isFromStorage=false)
    {
        // We cast from deepest to upper to leave the complexity of recursion
        // in this class and not the extensions
        $casted = [];
        foreach ($data as $key=>$value) {
            if (!is_array($value)) {
                $casted[$key] = $value;
                continue;
            }
            $type = $this->type($classOrInterface, $key, $value);
            $typeInfo = $this->typeInfo($type);
            $isClass = $this->isClass($typeInfo['type']);

            if (!$typeInfo['sequence']) {
                $casted[$key] = $isClass ? $this->fromArray($typeInfo['type'], $value, $isFromStorage) : $value;
                continue;
            }

            /** @var ListAdapter $listAdapter */
            $listAdapter = $this->listAdapters->forInstanceOf($classOrInterface);
            $casted[$key] = $listAdapter ? $listAdapter->newList($classOrInterface, $key) : [];

            foreach ($value as $subData) {
                $subValue = $isClass ? $this->fromArray($typeInfo['type'], (array)$subData, $isFromStorage) : $subData;
                if (!$listAdapter) {
                    $casted[$key][] = $subValue;
                    continue;
                }
                $listAdapter->addToList($classOrInterface, $key, $casted[$key], $subValue);
            }
        }
        if ($classOrInterface == stdClass::class) {
            return (object)$casted;
        }

        /** @var ObjectArrayConverterContract $converter */
        if ($converter = $this->handlers->forInstanceOf($classOrInterface)) {
            return $converter->fromArray($classOrInterface, $casted, $isFromStorage);
        }

        if (interface_exists($classOrInterface, false)) {
            throw new HandlerNotFoundException('No matching handler found for interface ' . $classOrInterface);
        }

        $object = new $classOrInterface();
        foreach ($casted as $key=>$value) {
            $object->$key = $value;
        }
        return $object;
    }

    /**
     * Create a new list.
     *
     * @param string $classOrInterface
     * @param string $path
     *
     * @return Traversable|array
     */
    public function newList(string $classOrInterface, string $path)
    {
        /** @var ListAdapter $adapter */
        if ($adapter = $this->listAdapters->forInstanceOf($classOrInterface)) {
            return $adapter->newList($classOrInterface, $path);
        }
        return [];
    }

    /**
     * Add an item to the list.
     *
     * @param string $classOrInterface
     * @param string $path
     * @param Traversable|array $list
     * @param mixed $item
     *
     * @return void
     */
    public function addToList(string $classOrInterface, string $path, &$list, &$item)
    {
        /** @var ListAdapter $adapter */
        if ($adapter = $this->listAdapters->forInstanceOf($classOrInterface)) {
            $adapter->addToList($classOrInterface, $path, $list, $item);
            return;
        }
        if (!is_array($list)) {
            throw new HandlerNotFoundException("No idea how to add items to " . Type::of($list));
        }
        $list[] = $item;
    }

    /**
     * Remove an item from the list.
     *
     * @param string $classOrInterface
     * @param string $path
     * @param Traversable|array $list
     * @param mixed $item
     *
     * @return void
     */
    public function removeFromList(string $classOrInterface, string $path, &$list, &$item)
    {
        /** @var ListAdapter $adapter */
        if ($adapter = $this->listAdapters->forInstanceOf($classOrInterface)) {
            $adapter->removeFromList($classOrInterface, $path, $list, $item);
            return;
        }
        if (!is_array($list)) {
            throw new HandlerNotFoundException("No idea how to remove items from " . Type::of($list));
        }
        if ($key = array_search($item, $list, true)) {
            unset($list[$key]);
        }
    }

    /**
     * Add a converter that will convert $classOrInterface.
     *
     * @param string                        $classOrInterface
     * @param ObjectArrayConverterContract  $converter
     *
     * @return $this
     */
    public function addConverter(string $classOrInterface, ObjectArrayConverterContract $converter)
    {
        $this->handlers->offsetSet($classOrInterface, $converter);
        if ($converter instanceof ListAdapter) {
            $this->listAdapters->offsetSet($classOrInterface, $converter);
        }
        return $this;
    }


    /**
     * Remove the previously assigned converter for $classOrInterface.
     *
     * @param string $classOrInterface
     *
     * @return $this
     */
    public function removeConverter(string $classOrInterface)
    {
        $this->handlers->offsetUnset($classOrInterface);
        if ($this->listAdapters->offsetExists($classOrInterface)) {
            $this->listAdapters->offsetUnset($classOrInterface);
        }
        return $this;
    }

    /**
     * Get the callable that was assigned to provide the types.
     *
     * @return callable|null
     */
    public function getTypeProvider()
    {
        return $this->typeProvider;
    }

    /**
     * Set a callable that will provide the type. The signature must be
     * like Ems\Contracts\Core\Extractor::type():
     *
     * function ($root, $path) {
     *     return \My\Orm\Project::class;
     * }
     * @param callable $provider
     * @return $this
     */
    public function setTypeProvider(callable $provider)
    {
        $this->typeProvider = $provider;
        return $this;
    }

    /**
     * @param string $class
     * @param string $key
     * @param array $data (optional)
     *
     * @return string
     */
    protected function type(string $class, string $key, array $data=[])
    {
        if ($this->typeProvider && $type = call_user_func($this->typeProvider, $class, $key)) {
            return $type;
        }
        // This seems to be irrelevant but I am not so sure to completely delete it.
        if (!isset($data[0])) {
            return stdClass::class;
        }
        if (is_array($data[0])) {
            $type = $this->type($class, $key, $data[0]);
            return $type.'[]';
        }
        if (is_object($data[0])) {
            return get_class($data[0]);
        }
        return gettype($data[0]);
    }

    /**
     * Split the type into a class/type and the fact it is a sequence.
     *
     * @param string $type
     * @return array
     */
    protected function typeInfo(string $type)
    {
        if (!$pos = strpos($type, '[]')) {
            return [
                'type'      => $type,
                'sequence'  => false
            ];
        }
        return [
            'type'      => substr($type, 0, $pos),
            'sequence'  => true
        ];
    }

    /**
     * @param string $type
     * @return bool
     */
    protected function isClass(string $type)
    {
        return !in_array($type, [
            'boolean',
            'integer',
            'double',
            'string',
            'array',
            'object',
            'resource',
            'resource (closed)',
            'NULL',
            'unknown type'
        ]);
    }

    /**
     * Ensure that there are only arrays in $data until $depth.
     *
     * @param array $data
     * @param int $depth
     *
     * @return array
     */
    private function ensureArray(array $data, int $depth)
    {
        $array = [];
        foreach ($data as $property=>$value) {

            if (is_object($value) && $depth > 0) {
                $array[$property] = $this->toArray($value, $depth-1);
                continue;
            }
            if (is_array($value) && $depth > 0) {
                $array[$property] = $this->ensureArray($value, $depth-1);
                continue;
            }
            $array[$property] = $value;
        }
        return $array;
    }
}