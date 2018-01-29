<?php


namespace Ems\Core;

use function call_user_func;
use function class_exists;
use Closure;
use Ems\Core\Exceptions\UnsupportedParameterException;
use function function_exists;
use function is_array;
use function is_callable;
use function is_object;
use function is_string;
use LogicException;
use function method_exists;
use ReflectionException;
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
 * callables which you don't know when registering them.
 *
 * The best way to understand how it is used look into its unit test.
 **/
class Lambda
{

    /**
     * @var string|array|callable
     **/
    protected $call;

    /**
     * @var callable
     */
    protected $callable;

    /**
     * @var array
     */
    protected $parsedCall;

    /**
     * @var string
     */
    protected $callClass;

    /**
     * @var object
     */
    protected $callInstance = false;

    /**
     * @var string
     */
    protected $callMethod;

    /**
     * @var bool
     */
    protected $isStaticMethod;

    /**
     * @var bool
     */
    protected $isFunction;

    /**
     * @var bool
     */
    protected $isClosure;

    /**
     * @var callable
     */
    protected $instanceResolver;

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
     * @var array
     */
    protected static $methodSeparators = ['::', '->', '@'];

    /**
     * Create a new Lambda object. Pass something what can be parsed by this
     * class. To resolve classes from class based calls inject a callable to
     * resolve this classes. (An IOCContainer or something similar)
     *
     * @param array|string|callable $call
     * @param callable              $instanceResolver (optional)
     **/
    public function __construct($call, callable $instanceResolver=null)
    {
        if ($call instanceof self) {
            throw new UnexpectedValueException("You cannot make a lambda to forward a lambda.");
        }

        $this->call = $call;
        $this->instanceResolver = $instanceResolver;

    }

    /**
     * Run the lambda code
     *
     * @return mixed
     *
     * @throws \ReflectionException
     **/
    public function __invoke()
    {
        $callable = $this->getCallable();

        if ($this->injections) {
            return static::callNamed($callable, $this->injections, func_get_args());
        }
        $args = array_merge(func_get_args(), $this->additionalArgs);
        return static::call($callable, $args);
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
     * Get the class of the callable. (if there is one)
     *
     * @return string
     *
     * @throws ReflectionException
     */
    public function getCallClass()
    {
        if ($this->callClass === null) {
            $this->parseCall();
        }
        return $this->callClass;
    }

    /**
     * Get the method|function of the callable (if there is one)
     *
     * @return string
     *
     * @throws ReflectionException
     */
    public function getCallMethod()
    {
        if ($this->callMethod === null) {
            $this->parseCall();
        }
        return $this->callMethod;
    }

    /**
     * @return null|object
     *
     * @throws ReflectionException
     */
    public function getCallInstance()
    {
        if ($this->callInstance !== false) {
            return $this->callInstance;
        }

        if (is_array($this->call) && isset($this->call[0]) && is_object($this->call[0])) {
            $this->callInstance = $this->call[0];
            return $this->callInstance;
        }

        if (is_object($this->call) && method_exists($this->call, '__invoke')){
            $this->callInstance = $this->call;
            return $this->callInstance;
        }

        if ($this->isFunction() || $this->isStaticMethod()) {
            $this->callInstance = null;
            return $this->callInstance;
        }

        $this->callInstance = $this->resolveCallInstance($this->getCallClass());

        return $this->callInstance;
    }

    /**
     * Return true if the callable is an instance method. This is true if the
     * there is a class and the method is not static. Also true on __invoke.
     *
     * @return bool
     *
     * @throws ReflectionException
     */
    public function isInstanceMethod()
    {
        return $this->getCallClass() && !$this->isClosure() && !$this->isStaticMethod();
    }

    /**
     * Return true if the callable is a method call and the reflection says it
     * is static.
     *
     * @return bool
     *
     * @throws ReflectionException
     */
    public function isStaticMethod()
    {
        if ($this->isStaticMethod === null) {
            $this->parseCall();
        }
        return $this->isStaticMethod;
    }

    /**
     * Return true if the callable is just a plain function.
     *
     * @return bool
     *
     * @throws ReflectionException
     */
    public function isFunction()
    {
        if ($this->isFunction === null) {
            $this->parseCall();
        }
        return $this->isFunction;
    }

    /**
     * Return true if the callable is a Closure.
     *
     * @return bool
     *
     * @throws ReflectionException
     */
    public function isClosure()
    {
        if ($this->isClosure === null) {
            $this->parseCall();
        }
        return $this->isClosure;
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
     *
     * @throws \ReflectionException
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
     *
     * @throws \ReflectionException
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
     * @param string|array|callable $callable
     * @param callable              $instanceResolver (optional)
     *
     * @return self
     **/
    public static function f($callable, callable $instanceResolver=null)
    {
        return new static($callable, $instanceResolver);
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
     *
     * @throws \ReflectionException
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
     *
     * @throws \ReflectionException
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
     *
     * @throws \ReflectionException
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
     * Return all method separators. These are used to split class
     * and method out of a string.
     *
     * @return array
     */
    public static function methodSeparators()
    {
        return static::$methodSeparators;
    }

    /**
     * Add another supported method separator.
     *
     * @param string $separator
     */
    public static function addMethodSeparator($separator)
    {
        static::$methodSeparators[] = $separator;
    }

    /**
     * Get the real callable to pass it to call_user_func.
     *
     * @return callable
     *
     * @throws ReflectionException
     */
    protected function getCallable()
    {
        if (!$this->callable) {
            $this->callable = $this->makeCallable();
        }
        return $this->callable;
    }

    /**
     * Make the (eventually not) callable base arg of this class callable.
     *
     * @return callable
     *
     * @throws ReflectionException
     */
    protected function makeCallable()
    {
        if ($this->call instanceof Closure || $this->isFunction()) {
            return $this->call;
        }

        if ($this->isStaticMethod()) {
            return [$this->getCallClass(), $this->getCallMethod()];
        }

        return [$this->getCallInstance(), $this->getCallMethod()];

    }

    /**
     * Parse the call to make it callable and know a few things about it.
     *
     * @throws ReflectionException
     * @throws LogicException
     */
    protected function parseCall()
    {

        if ($this->call instanceof Closure) {
            $this->callClass = Closure::class;
            $this->callMethod = '';
            $this->isStaticMethod = false;
            $this->isFunction = false;
            $this->isClosure = true;
            return;
        }

        list($class, $method) = $this->toClassAndMethod($this->call);

        if ($class === '') {
            $this->callClass = '';
            $this->callMethod = $method;
            $this->isStaticMethod = false;
            $this->isFunction = true;
            $this->isClosure = false;
            return;
        }

        $this->callClass = $class;
        $this->callMethod = $method;
        $this->isFunction = false;
        $this->isClosure = false;

        $reflection = new ReflectionMethod($class, $method);
        $this->isStaticMethod = $reflection->isStatic();

        if (!$reflection->isPublic()) {
            throw new LogicException("Method '$method' of $class is not public and therefore not callable.");
        }

        return;

    }

    /**
     * @param string|array $call
     *
     * @return array
     */
    protected function toClassAndMethod($call)
    {
        if (is_array($call)) {
            return is_object($call[0]) ? [get_class($call[0]), $call[1]] : $call;
        }

        if (is_object($call) && method_exists($call, '__invoke')) {
            return [get_class($call), '__invoke'];
        }

        if (function_exists($call)) {
            return ['', $call];
        }

        if (class_exists($call) && method_exists($call, '__invoke')) {
            return [$call, '__invoke'];
        }

        foreach (static::$methodSeparators as $separator) {
            if (strpos($call, $separator)) {
                return explode($separator, $call, 2);
            }
        }

        throw new UnsupportedParameterException("Cannot parse '$call'.");
    }

    /**
     * Resolve the class of a callable.
     *
     * @param string $class
     *
     * @return object
     */
    protected function resolveCallInstance($class)
    {
        if (!$this->instanceResolver) {
            return new $class();
        }
        return call_user_func($this->instanceResolver, $class);
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
     *
     * @throws \ReflectionException
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
     *
     * @throws \ReflectionException
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
