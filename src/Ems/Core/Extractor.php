<?php

namespace Ems\Core;

use Ems\Contracts\Core\Extractor as ExtractorContract;
use Ems\Core\Patterns\ExtendableTrait;
use InvalidArgumentException;

class Extractor implements ExtractorContract
{
    use ExtendableTrait;

    /**
     * @var string
     **/
    protected $separator = '.';

    /**
     * @var array
     **/
    protected $typeCache = [];

    /**
     * {@inheritdoc}
     *
     * @param mixed  $root (object|array|classname)
     * @param string $path
     *
     * @return mixed
     **/
    public function value($root, $path)
    {
        if (!is_array($root) && !is_object($root)) {
            return;
        }

        if (!$this->isNestedKey($path)) {
            return $this->getNode($root, $path);
        }

        return $this->getNestedValue($root, $path);
    }

    /**
     * {@inheritdoc}
     * This class has no clue about getting the types of a class/object
     * model. In eloquent you would read a relation and return its target
     * class. In propel you would ask a mapbuilder. The extractor acts
     * as a proxy for a callable you assign to read the relations. It just
     * cares about the nesting stuff so that your callable can just handle
     * typeOf($object, $key) without nested keys.
     *
     *
     * @param mixed  $root
     * @param string $path (optional)
     *
     * @return string
     **/
    public function type($root, $path = null)
    {
        if ($path === null) {
            return is_object($root) ? get_class($root) : gettype($root);
        }

        if (is_array($root)) {
            return $this->type($this->value($root, $path));
        }

        $rootObject = $this->rootInstance($root);

        if ($result = $this->getFromCache($rootObject, $path)) {
            return $result;
        }

        $segments = explode($this->separator, $path);

        $segmentCount = count($segments);

        $currentObject = $rootObject;
        $foundType = null;

        $i = 1;
        foreach ($segments as $segment) {
            $type = $this->callUntilNotNull([$currentObject, $segment]);

            if ($i == $segmentCount) {
                $foundType = $type;
                break;
            }

            $currentObject = $type == 'stdClass' ? $this->value($currentObject, $segment) : $this->rootInstance($type);

            ++$i;
        }

        if (!$foundType) {
            return;
        }

        return $this->putIntoCache($rootObject, $path, $foundType);
    }

    /**
     * @param string $key
     *
     * @return bool
     **/
    protected function isNestedKey($key)
    {
        return (int) mb_strpos($key, $this->separator) > 0;
    }

    /**
     * Search inside an hierarchy by a nested key.
     *
     * @param mixed  $root (object|array|classname)
     * @param string $path
     *
     * @return mixed
     **/
    protected function getNestedValue($root, $path)
    {
        $node = &$root;

        $segments = explode($this->separator, $path);

        $last = count($segments) - 1;
        $varname = $segments[0];

        for ($i = 0; $i <= $last; ++$i) {
            $node = @$this->getNode($node, $segments[$i]);

            if ($node === null) {
                return;
            }

            if (!is_scalar($node)) {
                continue;
            }

            if ($i == $last) {
                return $node;
            }

            return;
        }
    }

    /**
     * Returns an array key if node is an array else a property.
     *
     * @param mixed  $node (array|object)
     * @param string $key
     *
     * @return mixed
     **/
    protected function &getNode(&$node, $key)
    {
        if (is_object($node) && isset($node->$key)) {
            return $node->$key;
        }

        if (is_array($node) && isset($node[$key])) {
            return $node[$key];
        }

        $result = null; //$this->callUntilNotNull($node, $key);

        return $result;
    }

    /**
     * Return an object not a class.
     *
     * @param string|object $root
     *
     * @return object
     **/
    protected function rootInstance($root)
    {
        if (is_object($root)) {
            return $root;
        }

        if (!is_string($root)) {
            throw new InvalidArgumentException('If you pass a path to type() it has to be array, object or a class not '.gettype($root));
        }

        if (!class_exists($root)) {
            throw new InvalidArgumentException("The passed class '$root' does not exist");
        }

        return new $root();
    }

    /**
     * Get a type from cache.
     *
     * @param object $rootObject
     * @param string $path
     *
     * @return string
     **/
    protected function getFromCache($rootObject, $path)
    {
        $cacheId = $this->cacheId($rootObject, $path);

        return isset($this->typeCache[$cacheId]) ? $this->typeCache[$cacheId] : null;
    }

    /**
     * Put a type into cache and return it.
     *
     * @param object $rootObject
     * @param string $path
     * @param string $type
     *
     * @return string
     **/
    protected function putIntoCache($rootObject, $path, $type)
    {
        $cacheId = $this->cacheId($rootObject, $path);
        $this->typeCache[$cacheId] = $type;

        return $type;
    }

    /**
     * Generate a cache id for a type query.
     *
     * @param object $rootObject
     * @param string $path
     *
     * @return string
     **/
    protected function cacheId($rootObject, $path)
    {
        return get_class($rootObject)."|$path";
    }
}
