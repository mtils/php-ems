<?php


namespace Ems\Core\Patterns;

use Ems\Core\Exceptions\HandlerNotFoundException;


trait StaticExtendable
{
    /**
     * @var array
     **/
    protected static $_extensions = [];

    /**
     * @var array
     **/
    protected static $_booted = [];

    /**
     * @var array
     **/
    protected static $_booters = [];

    /**
     * Add an "extension". A callable (array, closure,..) which You can call by
     * YouClass::$name()
     *
     * @param string $name
     * @param callable $callable
     * @return self
     **/
    public static function extend($name, callable $callable)
    {
        $class = get_called_class();

        if (!isset(static::$_extensions[$class])) {
            static::$_extensions[$class] = [];
        }
        static::$_extensions[$class][$name] = $callable;
    }

    /**
     * Forward methods to another object
     *
     * @param string|array $methods
     * @param object $object
     **/
    public static function forward($methods, $object)
    {
        foreach((array)$methods as $method) {
            static::extend($method, static::buildForward($method, $object));
        }
    }

    /**
     * This is the preferred way to assign your extensions. If you have one
     * static class like \NS\Facade. you would not include every inherited
     * Facade by calling \NS\UserFacade::extend(). This would include all
     * unneeded classes (and files). So instead call the method on your base
     * facade (\NS\Facade::bootWith('\NS\UserFacade', function(){ UserFacade::extend})
     *
     * @param string $class
     * @param callable $booter
     **/
    public static function bootWith($class, callable $booter)
    {
        if (!isset(static::$_booters[$class])) {
            static::$_booters[$class] = [];
        }
        static::$_booters[$class][] = $booter;
    }

    /**
     * Return if an extension with name $name exists
     *
     * @param string $name
     * @return bool
     **/
    public static function hasExtension($name)
    {
        static::bootOnce();
        return isset(static::$_extensions[get_called_class()][$name]);
    }

    /**
     * Return the extension named $name
     *
     * @param string $name
     * @return mixed
     **/
    public static function getExtension($name)
    {
        if (static::hasExtension($name)) {
            return static::$_extensions[get_called_class()][$name];
        }

        throw new HandlerNotFoundException(get_called_class() . ": No extension named \"$name\" found");
    }

    /**
     * Call the extension named $name with $params
     *
     * @param string $name
     * @param array $params (optional)
     * @return mixed
     **/
    public static function callExtension($name, array $params=[])
    {

        $extension = static::getExtension($name);

        // call_user_func_array seems to be slow
        switch (count($params)) {
            case 0:
                return call_user_func($extension);
            case 1:
                return call_user_func($extension, $params[0]);
            case 2:
                return call_user_func($extension, $params[0], $params[1]);
            case 3:
                return call_user_func($extension, $params[0], $params[1], $params[2]);
            case 4:
                return call_user_func($extension, $params[0], $params[1], $params[2], $params[3]);
            default:
                return call_user_func_array($extension, $params);
        }


    }

    /**
     * Builds a forward to another object to adds it via extend()
     *
     * @param string $method
     * @param object $object
     **/
    protected static function buildForward($method, $object)
    {
        return function() use ($method, $object) {

             $args = func_get_args();

             switch(count($args)) {
                case 0:
                    return $object->$method();
                case 1:
                    return $object->$method($args[0]);
                case 2:
                    return $object->$method($args[0], $args[1]);
                case 3:
                    return $object->$method($args[0], $args[1], $args[2]);
                case 4:
                    return $object->$method($args[0], $args[1], $args[2], $args[3]);
                default:
                    return call_user_func_array([$object, $method], $args);
             }

        };
    }

    /**
     * Call all booters to boot this class
     *
     * @return null
     **/
    protected static function bootOnce()
    {

        $class = get_called_class();

        if (isset(static::$_booted[$class])) {
            return;
        }

        static::callBooters($class);


        static::$_booted[$class] = true;

    }

    /**
     * Calls all booters (if any)
     *
     * @param string $class
     * @return null
     **/
    protected static function callBooters($class)
    {
        if (!isset(static::$_booters[$class])) {
            return;
        }
        foreach (static::$_booters[$class] as $booter) {
            call_user_func($booter, $class);
        }
    }

}
