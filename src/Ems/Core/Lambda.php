<?php


namespace Ems\Core;

use UnexpectedValueException;
use ReflectionMethod;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use Ems\Core\Exceptions\KeyNotFoundException;

/**
 * This class is a helper class to allow inline evaluation of
 * parameters in dynamic calls like Container::call
 *
 * It also allows you to add additional parameters to your
 * callables which you dont know when registering them.
 *
 * The best way to understand how it is used look into its unittest.
 **/
class Lambda
{

    /**
     * @var callable
     **/
    protected $call;

    /**
     * @var array
     **/
    protected $additionalArgs = [];

    /**
     * @var array
     **/
    protected $injections = [];

    /**
     * @var array
     **/
    protected static $reflectionCache = [];

    /**
     * @param callable $call
     **/
    public function __construct(callable $call)
    {
        if ($call instanceof self) {
            throw new UnexpectedValueException("You cannot make a lambda to forward a lambda.");
        }
        $this->call = $call;
    }

    /**
     * Run the lambda code
     *
     * @return mixed
     **/
    public function __invoke()
    {
        if ($this->injections) {
            return static::callNamed($this->call, $this->injections, func_get_args());
        }
        $args = array_merge(func_get_args(), $this->additionalArgs);
        return static::call($this->call, $args);
    }

    /**
     * Append some parameters to the passed parameters. They will be injected
     * when the assigned callable is called.
     * Append parameters may also contain Lambdas.
     *
     * @param mixed $parameters
     *
     * @return self
     **/
    public function append($parameters)
    {
        $this->additionalArgs = is_array($parameters) ? $parameters : func_get_args();
        return $this;
    }

    /**
     * Same as append, but replace all callables with lambdas.
     *
     * @see self::append()
     *
     * @param mixed $parameters
     *
     * @return self
     **/
    public function curry($parameters)
    {
        $parameters = is_array($parameters) ? $parameters : func_get_args();
        return $this->append(static::currify($parameters));
    }

    /**
     * Assign an associative array with a value for each parameter of the callable.
     * The parameters will then be passed to it. (And merged with the normal ones)
     *
     * @param array $injections
     *
     * @return self
     **/
    public function inject(array $injections)
    {
        $this->injections = $injections;
        return $this;
    }

    /**
     * Just call a callable without parsing any lambdas
     *
     * @param callable $callable
     * @param mixed    $args (optional)
     *
     * @return mixed
     **/
    public static function callFast(callable $callable, $args=null)
    {

        if (!is_array($args)) {
            return call_user_func($callable, $args === null ? [] : [$args]);
        }

        switch (count($args)) {
            case 0:
                return call_user_func($callable);
            case 1:
                return call_user_func($callable, $args[0]);
            case 2:
                return call_user_func($callable, $args[0], $args[1]);
            case 3:
                return call_user_func($callable, $args[0], $args[1], $args[2]);
            case 4:
                return call_user_func($callable, $args[0], $args[1], $args[2], $args[3]);
        }

        return call_user_func_array($callable, $args);
    }

    /**
     * Call a callable and parse its lambdas
     *
     * @param callable $callable
     * @param mixed    $args (optional)
     *
     * @return mixed
     **/
    public static function call(callable $callable, $args=null)
    {

        if (!is_array($args)) {
            $args = $args === null ? [] : [$args];
        }

        return static::callFast($callable, static::evaluateArgs($args));

    }

    /**
     * Calls the passed callable with an array of named parameter
     * ($parameterName=>$value).
     * If you want to pass original parameters to overwrite the injected ones
     * pass them as the third parameter.
     *
     * @see self::toArguments()
     *
     * @param callable $callable
     * @param array    $inject
     * @param array    $callArgs (optional)
     *
     * @return array
     **/
    public static function callNamed(callable $callable, array $inject, array $callArgs=[])
    {
        return static::call($callable, static::toArguments($callable, $inject, $callArgs));
    }

    /**
     * Calls the passed callable with an array of named parameter
     * ($parameterName=>$value).
     * If you want to pass additional parameters which gets inserted in every
     * position where an inject key is missing, pass them as the third parameter.
     *
     * @see self::mergeArguments()
     *
     * @param callable $callable
     * @param array    $inject
     * @param array    $callArgs (optional)
     *
     * @return array
     **/
    public static function callMerged(callable $callable, array $inject, array $callArgs=[])
    {
        return static::call($callable, static::mergeArguments($callable, $inject, $callArgs));
    }

    /**
     * Run all Lambdas in arguments anf return the "parsed"
     *
     * @param array
     *
     * @return array
     **/
    public static function evaluateArgs(array $args)
    {

        $parsedArgs = [];

        foreach ($args as $key=>$value) {

            if ($value instanceof self) {
                $parsedArgs[$key] = static::callFast($value);
                continue;
            }
            $parsedArgs[$key] = $args[$key];
        }

        return $parsedArgs;

    }

    /**
     * Replace all callables in an array with lambdas
     *
     * @param array $args
     *
     * @return array
     **/
    public static function currify(array $args)
    {
        $curried = [];

        foreach ($args as $key=>$value) {
            if (is_callable($value)) {
                $curried[$key] = new static($value);
                continue;
            }
            $curried[$key] = $value;
        }

        return $curried;
    }

    /**
     * Static constructor for shorter instance creation
     *
     * @param callable $callable
     *
     * @return self
     **/
    public static function f(callable $callable)
    {
        return new static($callable);
    }

    /**
     * Return an array of argument information about $callable.
     * The array is indexed by the name and every value is an array
     * containing information about if its optional and which type
     * it should have.The callable typehint is removed to support
     * protected methods.
     *
     * @param callable|array $callable
     *
     * @return array
     **/
    public static function reflect($callable)
    {
        $cacheId = static::cacheId($callable);

        if ($reflectArray = static::getFromCache($cacheId)) {
            return $reflectArray;
        }

        $parameters = [];

        foreach(static::getReflection($callable)->getParameters() as $parameter) {
            $type = null;
            if ($classReflection = $parameter->getClass()) {
                $type = $classReflection->getName();
            }
            $parameters[$parameter->getName()] = [
                'optional' => $parameter->isOptional(),
                'type'     => $type
            ];
        }

        return static::putIntoCacheAndReturn($cacheId, $parameters);

    }

    /**
     * Reads all parameter names from the callable and builds an parameter array
     * you can use to call the callable.
     * The second parameter is an associative array with data for each parameter.
     * The third parameter is what you "normally" would pass to the callable.
     * (A numeric array containing parameters)
     * The numeric parameters do overwrite the assoc one. The callable
     * typehint is removed to support protected methods.
     *
     * @param callable|array $callable
     * @param array          $inject
     * @param array          $callArgs (optional)
     *
     * @return array
     **/
    public static function toArguments($callable, array $inject, array $callArgs=[])
    {

        $i = -1;
        $arguments = [];

        foreach(static::reflect($callable) as $name=>$data) {

            $i++;

            // isset() does not work with null, so it would not be possible to
            // overwrite values with null, so instead use array_key_exists
            if (array_key_exists($i, $callArgs)) {
                $arguments[$i] = $callArgs[$i];
                continue;
            }

            if (array_key_exists($name, $inject)) {
                $arguments[$i] = $inject[$name];
                continue;
            }

            if (!$data['optional']) {
                $cacheId = static::cacheId($callable);
                throw new KeyNotFoundException("The parameter $name is required by '$cacheId'. Pass it via inject or the normal parameters.");
            }
        }

        return $arguments;

    }

    /**
     * Same as self::toArguments. But instead of overwriting the $inject
     * parameters with the callArgs they are inserted on any position where
     * a injected value is missing.
     * The typehint is removed to support protected methods.
     *
     * @param callable|array $callable
     * @param array          $inject
     * @param array          $callArgs (optional)
     *
     * @return array
     **/
    public static function mergeArguments($callable, array $inject, array $callArgs=[])
    {
        $arguments = [];

        foreach (static::reflect($callable) as $paramName=>$methodData) {
            if (isset($inject[$paramName])) {
                $arguments[] = $inject[$paramName];
                continue;
            }

            $arguments[] = array_shift($callArgs);
        }

        return $arguments;
    }

    /**
     * Try to load a reflection from cache.
     *
     * @param string $cacheId
     *
     * @return mixed
     **/
    protected static function getFromCache($cacheId)
    {
        return isset(static::$reflectionCache[$cacheId]) ? static::$reflectionCache[$cacheId] : null;
    }

    /**
     * Store the reflected information about $cacheId and return it
     *
     * @param string $cacheId
     * @param array $reflection
     *
     * @return array
     **/
    protected static function putIntoCacheAndReturn($cacheId, array $reflection)
    {
        static::$reflectionCache[$cacheId] = $reflection;
        return $reflection;
    }

    /**
     * Return a cache id for the passed callable
     *
     * @param callable|array $callable
     *
     * @return string
     **/
    protected static function cacheId($callable)
    {

        if (is_array($callable)) {
            $class = is_string($callable[0]) ? $callable[0] : get_class($callable[0]);
            return "$class::" . $callable[1];
        }

        if (is_string($callable)) {
            return $callable;
        }

        if (!$callable instanceof \Closure) {
            return get_class($callable);
        }

        $r = new ReflectionFunction($callable);
        return 'Closure:' . $r->getFileName() . ':' . $r->getStartLine() . ':' . $r->getEndLine();

    }

    /**
     * Get a reflection object for the passed callable. The callable
     * typehint is removed to support protected methods.
     *
     * @param callable|array $callable
     *
     * @return ReflectionFunctionAbstract
     **/
    protected static function getReflection($callable)
    {

        if (is_array($callable)) {
            return new ReflectionMethod($callable[0], $callable[1]);
        }

        if ($callable instanceof \Closure) {
            return new ReflectionFunction($callable);
        }

        if (is_object($callable)) {
            return new ReflectionMethod($callable, '__invoke');
        }

        // Should be a string until here
        if (strpos($callable, '::')) {
            list($class, $method) = explode('::', $callable);
            return new ReflectionMethod($class, $method);
        }

        return new ReflectionFunction($callable);
    }

}
