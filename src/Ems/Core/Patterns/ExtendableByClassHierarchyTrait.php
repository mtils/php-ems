<?php

namespace Ems\Core\Patterns;

use Ems\Core\Exceptions\HandlerNotFoundException;
use Ems\Core\Helper;
use OutOfBoundsException;

/**
 * This trait also implements the Extendable interface but searches handlers
 * by a class hierarchy.
 * You could have this hierarchy:
 *
 * class Model
 * class User extends Model
 * class CmsUser extends User
 *
 * Then you could call $obj->extend(Model::class, $callable)
 * And this callable will be called on Model, User and CmsUser.
 * Until you call $obj->extend(User::class, $callable), because User is nearer
 * then Model.
 *
 * @see \Ems\Contracts\Core\Extendable
 **/
trait ExtendableByClassHierarchyTrait
{
    /**
     * Here the callables are held.
     *
     * @var array
     **/
    protected $_extensions = [];

    /**
     * @var array
     **/
    protected $_cache = [];

    /**
     * {@inheritdoc}
     *
     * @param string   $name
     * @param callable $callable
     *
     * @return self
     **/
    public function extend($name, callable $callable)
    {
        $this->_extensions[$name] = $callable;

        return $this;
    }

    /**
     * Return the extension named $name.
     *
     * @param string $name
     *
     * @return mixed
     **/
    public function getExtension($name)
    {
        if ($extension = $this->nearestForClass($name)) {
            return $extension;
        }

        throw new OutOfBoundsException(get_class($this).": No extension named \"$name\" found");
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     **/
    public function extensions()
    {
        return array_keys($this->_extensions);
    }

    /**
     * This method finds the best matching callable for class $class.
     * It decorates findNearestForClass to cache the result
     *
     * @param string $findClass
     *
     * @return callable|null
     **/
    protected function nearestForClass($findClass)
    {

        if (isset($this->_cache[$findClass])) {
            return $this->_cache[$findClass];
        }

        if (!count($this->_extensions)) {
            return null;
        }

        if (isset($this->_extensions[$findClass])) {
            $this->_cache[$findClass] = $this->_extensions[$findClass];
            return $this->_cache[$findClass];
        }

        if (!$nearest = $this->findNearestForClass($this->_extensions, $findClass)) {
            return null;
        }

        $this->_cache[$findClass] = $nearest;

        return $nearest;

    }

    /**
     * This method does the actual work to find the best matching callable for
     * class $class
     *
     * @param array $providers
     * @param string $findClass
     * @return callable|null
     **/
    protected function findNearestForClass(&$providers, $findClass)
    {

        if (!$all = $this->findAllForClass($providers, $findClass)) {
            return null;
        }

        if (count($all) == 1) {
            return array_values($all)[0];
        }

        foreach ($this->inheritance($findClass) as $parentClass) {
            if (isset($all[$parentClass]) ) {
                return $all[$parentClass];
            }
        }

        return null;
    }

    /**
     * Returns all providers which are assigned for $findClass or one of its
     * parent classes
     *
     * @param array $providers
     * @param string $findClass
     * @return array
     **/
    protected function findAllForClass(&$providers, $findClass)
    {

        $all = [];

        foreach ($providers as $class=>$provider) {
            if (is_subclass_of($findClass, $class) || $findClass == $class) {
                $all[$class] = $provider;
            }
        }

        return $all;

    }

    /**
     * Call the extension named $name with $params.
     *
     * @param string $name
     * @param mixed $params (optional)
     *
     * @return mixed
     **/
    protected function callExtension($name, $params = [])
    {
        return Helper::call($this->getExtension($name), $params);
    }

    /**
     * Return the class inheritance of $class
     *
     * @param string $class
     *
     * @return array
     **/
    protected function inheritance($class)
    {
        $parents = class_parents($class, false);
        return [$class] + $parents;
    }
}
