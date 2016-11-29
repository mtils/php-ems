<?php

namespace Ems\Core;

use Ems\Contracts\Core\Extractor as ExtractorContract;
use InvalidArgumentException;

class Extractor implements ExtractorContract
{
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
     * Search inside an hierarchy by a nested key
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

        // Special handling of eloquent relations
        if (!$this->isEloquentModel($node)) {
            $result = null;

            return $result;
        }

        if (!method_exists($node, $key)) {
            $result = null;

            return $result;
        }

        $relation = $node->{$key}();

        if (!$this->isEloquentRelation($relation)) {
            $result = null;

            return $result;
        }

        return $relation->getResults();
    }

    /**
     * Return true if $node is an eloquent model (without needing 
     * the actual classes).
     *
     * @param mixed $node
     *
     * @return bool
     **/
    protected function isEloquentModel($node)
    {
        return is_object($node) && is_subclass_of($node, 'Illuminate\Database\Eloquent\Model', false);
    }

    /**
     * Return true if $node is an eloquent relation (without needing
     * the actual classes).
     *
     * @param mixed $relation
     *
     * @return bool
     **/
    protected function isEloquentRelation($relation)
    {
        return is_object($relation) && is_subclass_of($relation, 'Illuminate\Database\Eloquent\Relations\Relation', false);
    }

    /**
     * Return an object not a class
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
            throw new InvalidArgumentException("If you pass a path to type() it has to be array, object or a class not " . gettype($root));
        }

        if (!class_exists($root)) {
            throw new InvalidArgumentException("The passed class '$root' does not exist");
        }

        return new $root;

    }

    /**
     * Generate a cache id for a type query
     *
     * @param object $rootObject
     * @param string $path
     * @return string
     **/
    protected function getFromCache($rootObject, $path)
    {
        $cacheId = $this->cacheId($rootObject, $path);
        return isset($this->typeCache[$cacheId]) ? $this->typeCache[$cacheId] : null;
    }

    /**
     * Generate a cache id for a type query
     *
     * @param object $rootObject
     * @param string $path
     * @return string
     **/
    protected function cacheId($rootObject, $path)
    {
        return get_class($rootObject) . "|$path";
    }

}
