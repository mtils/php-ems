<?php

namespace Ems\Core;

use ArrayAccess;
use Ems\Contracts\Core\HasMethodHooks;
use Ems\Contracts\Core\Input;
use Ems\Contracts\Core\InputConnection;
use Ems\Contracts\Core\IOCContainer as ContainerContract;
use Ems\Contracts\Core\OutputConnection;
use Ems\Contracts\Core\Stringable;
use Ems\Contracts\Core\Type;
use Ems\Contracts\Core\Url as UrlContract;
use Ems\Core\Exceptions\KeyNotFoundException;
use Ems\Core\Exceptions\UnsupportedUsageException;
use Ems\Core\Patterns\ListenerContainer;
use Ems\Core\Support\IOCContainerProxyTrait;
use LogicException;
use Psr\Log\LoggerInterface;

use Traversable;

use function get_class;
use function in_array;
use function is_callable;
use function is_object;

/**
 * This application is a minimal version optimized
 * for flexibility and less dependencies.
 * Somewhere in you app directory make this:.
 *
 * $app = (new Application)->setName('My App')
 *                         ->setVersion('2.0.3')
 *                         ->setPath(realpath('../'))
 *
 * There should be only one real singleton: the application. If we could
 * work without any singleton it would be better but that is very clumsy.
 * Globals are bad, and statics (and therefore singletons) are globals so
 * please avoid putting just everything you need into a singleton instance.
 *
 * The only things which are allowed to be put into the application are
 * the things which belong to the application. These are in this case:
 *
 * - bindings (therefore it implements IOCContainer)
 * - config (you configure your Application once)
 * - paths (the static paths of your application)
 *
 * What is meant here: If for example the view directory changes while
 * processing the request, it should not be assigned to config or any singleton.
 * Only the things which will not change after deploying your application
 * should be stored in it.
 *
 * Dont use config, paths or whatever to store temporary values. If values
 * belong to a request or console call, store them in the request because they
 * belong to _this_ request. Then you have to pass your request just everywhere.
 *
 **/
class Application implements ContainerContract, HasMethodHooks
{
    use IOCContainerProxyTrait;

    /**
     * @var string
     */
    const PRODUCTION = 'production';

    /**
     * @var string
     */
    const LOCAL = 'local';

    /**
     * @var string
     */
    const TESTING = 'testing';

    /**
     * @var string
     **/
    protected $name = '';

    /**
     * @var string
     **/
    protected $version = '';

    /**
     * @var array|ArrayAccess
     */
    protected $config = [];

    /**
     * @var array|ArrayAccess
     */
    protected $paths = [];

    /**
     * @var UrlContract
     **/
    protected $path;

    /**
     * @var UrlContract
     **/
    protected $url;

    /**
     * @var string
     **/
    protected $urlProvider;

    /**
     * @var string
     */
    protected $environment;

    /**
     * @var callable
     */
    protected $environmentProvider;

    /**
     * @var bool
     */
    protected $wasBooted = false;

    /**
     * @var ListenerContainer
     */
    protected $listeners;

    /**
     * @var static
     */
    protected static $staticInstance;

    /**
     * @var ContainerContract
     */
    protected static $staticContainer;

    /**
     * Application constructor.
     *
     * @param $path
     * @param ContainerContract|null $container
     * @param bool $bindAsApp (default:true)
     */
    public function __construct($path, ContainerContract $container = null, $bindAsApp=true)
    {
        $this->path = new Url($path);
        $this->listeners = new ListenerContainer();
        $container = $container ?: new IOCContainer();
        $this->setContainer($container);
        if ($bindAsApp) {
            $this->container->instance('app', $this);
        }
        $this->container->instance(static::class, $this);
    }

    //<editor-fold desc="Getters and Setters">
    /**
     * Return the application name.
     *
     * @return string
     **/
    public function name()
    {
        return $this->name;
    }

    /**
     * Set the application name.
     *
     * @param string $name
     *
     * @return self
     **/
    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Return the application version.
     *
     * @return string
     **/
    public function version()
    {
        return $this->version;
    }

    /**
     * Set the application version.
     *
     * @param string
     *
     * @return self
     **/
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }
    //</editor-fold>

    //<editor-fold desc="Paths and URLs">

    /**
     * Return the url of the application itself. This is the one single url that
     * never changes. Mostly it is the url of the project working on this app.
     *
     * It is NOT HTTP_HOST.
     *
     * Urls can change during a request or you need a specific url for a user
     * or you run a multi virtual host application.
     * So for url generation use other classes but not the application.
     *
     * @return UrlContract
     **/
    public function url()
    {
        return $this->url;
    }

    /**
     * Set the url of the application.
     *
     * @param UrlContract $url
     *
     * @return self
     **/
    public function setUrl(UrlContract $url)
    {
        $this->url = $url;
        return $this;
    }

    /**
     * Return an array(like) of application paths indexed by a name.
     * (e.g. ['public' => $appRoot/public])
     *
     * @return array|ArrayAccess
     */
    public function getPaths()
    {
        return $this->paths;
    }

    /**
     * Set the application paths.
     *
     * @see self::getPaths()
     *
     * @param $paths
     *
     * @return $this
     */
    public function setPaths($paths)
    {
        $this->paths = Type::forceAndReturn($paths, ArrayAccess::class);
        return $this;
    }

    /**
     * This is a method to retrieve paths of the application.
     * Without any parameter it returns the root path. This is
     * typically a directory root with a public folder.
     *
     * This path is set in __construct() and cannot be changed.
     *
     * To get any other path, just pass a name or an url.
     *
     *
     * @param string|null $name
     *
     * @return UrlContract
     */
    public function path($name=null)
    {
        if (!$name || in_array($name, ['/', '.', 'root', 'app'])) {
            return $this->path;
        }

        list($scope, $path) = $this->splitNameAndPath($name);

        // If no scope was passed, just return an absolute url appended to root path
        if (!$scope) {
            return $this->path->append($path);
        }

        if (!isset($this->paths[$scope])) {
            throw new KeyNotFoundException("No path found with name '$scope'");
        }

        $url = $this->paths[$scope] instanceof UrlContract ? $this->paths[$scope] : new Url($this->paths[$scope]);

        return $path ? $url->append($path) : $url;

    }

    /**
     * Get the application configuration.
     *
     * @return array|ArrayAccess
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Set the application configuration.
     *
     * @param array|ArrayAccess $config
     *
     * @return self
     */
    public function setConfig($config)
    {
        if ($this->wasBooted()) {
            throw new UnsupportedUsageException('You can only set configuration before boot.');
        }
        $this->config = Type::forceAndReturn($config, ArrayAccess::class);
        return $this;
    }

    /**
     * Return a configuration value. Pass nothing and get the complete config.
     * Pass a key and you get the value of that key, or if it does not exist
     * $default.
     *
     * @param string $key
     * @param mixed $default (optional)
     *
     * @return mixed
     */
    public function config(string $key, $default=null)
    {
        if (isset($this->config[$key])) {
            return $this->config[$key];
        }

        return $default;
    }

    /**
     * Set a configuration value. To prevent misuse the configuration values can
     * not be changed after booting. Otherwise somebody will use it as a global
     * variable storage.
     *
     * @param string|array|ArrayAccess $key
     * @param mixed                    $value (optional)
     *
     * @return self
     */
    public function configure($key, $value=null)
    {
        if ($this->wasBooted()) {
            throw new UnsupportedUsageException('You can only set configuration before boot.');
        }

        if (!is_array($key) && !$key instanceof Traversable && $value !== null) {
            $this->config[$key] = $value;
            return $this;
        }

        foreach ($key as $configKey=>$value) {
            $this->configure($configKey, $value);
        }

        return $this;

    }

    //</editor-fold>

    //<editor-fold desc="Boot Process">
    /**
     * Boot the application. Whatever this means is only determined by the
     * listeners to onBefore('boot') or onAfter('boot').
     *
     */
    public function boot()
    {
        $this->listeners->call('boot', [$this], ListenerContainer::POSITIONS);
        $this->wasBooted = true;
        static::$staticInstance = $this;
        static::$staticContainer = $this->container;
    }

    /**
     * Alias for self::wasBooted().
     *
     * @return bool
     */
    public function isBooted()
    {
        return $this->wasBooted;
    }

    /**
     * @return bool
     */
    public function wasBooted()
    {
        return $this->wasBooted;
    }
    //</editor-fold>

    //<editor-fold desc="Environment">
    /**
     * Return the application environment.
     *
     * @return string
     */
    public function environment()
    {
        if ($this->environment) {
            return $this->environment;
        }

        if (!$this->environmentProvider) {
            $this->environment = static::PRODUCTION;
            return $this->environment;
        }

        $this->setEnvironment(call_user_func($this->environmentProvider, $this));

        return $this->environment;
    }

    /**
     * Set the application environment.
     *
     * @param string $env
     *
     * @return self
     */
    public function setEnvironment(string $env)
    {
        $this->environment = $env;
        return $this;
    }

    /**
     * Set a callable to detect the environment.
     *
     * @param callable $provider
     *
     * @return $this
     */
    public function provideEnvironment(callable $provider)
    {
        $this->environmentProvider = $provider;
        $this->environment = '';
        return $this;
    }
    //</editor-fold>

    //<editor-fold desc="Events and Hooks">
    /**
     * @return array
     */
    public function methodHooks()
    {
        return ['boot'];
    }

    /**
     * {@inheritDoc}
     *
     * @param string|object $event
     * @param callable $listener
     *
     * @return self
     **/
    public function onBefore($event, callable $listener)
    {
        return $this->storeListener($event, $listener, ListenerContainer::BEFORE);
    }

    /**
     * {@inheritDoc}
     *
     * @param string|object $event
     * @param callable $listener
     *
     * @return self
     **/
    public function on($event, callable $listener)
    {
        return $this->storeListener($event, $listener, ListenerContainer::ON);
    }

    /**
     * {@inheritDoc}
     *
     * @param string|object $event
     * @param callable $listener
     *
     * @return self
     **/
    public function onAfter($event, callable $listener)
    {
        return $this->storeListener($event, $listener, ListenerContainer::AFTER);
    }

    /**
     * @param string|object $event
     * @param callable      $listener
     * @param string        $position
     *
     * @return $this
     */
    protected function storeListener($event, callable $listener, string $position)
    {
        $abstract = is_object($event) ? get_class($event) : $event;
        if (in_array($abstract, $this->methodHooks())) {
            $this->listeners->add($abstract, $listener, $position);
            return $this;
        }
        switch ($position) {
            case ListenerContainer::BEFORE:
                $this->container->onBefore($event, $listener);
                return $this;
            case ListenerContainer::ON:
                $this->container->on($event, $listener);
                return $this;
            default:
                $this->container->onAfter($event, $listener);
        }
        return $this;

    }

    /**
     * {@inheritDoc}
     *
     * @param string|object $event
     * @param string $position ('after'|'before'|'')
     *
     * @return array
     **/
    public function getListeners($event, $position = '')
    {
        $abstract = is_object($event) ? get_class($event) : $event;
        if (in_array($abstract, $this->methodHooks())) {
            return $this->listeners->get($event, $position);
        }
        return $this->container->getListeners($event, $position);
    }



    //</editor-fold>

    //<editor-fold desc="IO Shortcuts">

    /**
     * This is a shortcut to read from the input connection
     *
     * @param callable|null $into
     *
     * @return Input
     *
     * @see InputConnection::read()
     */
    public function read(callable $into=null)
    {
        return $this->container->get(InputConnection::class)->read($into);
    }

    /**
     * This is a shortcut to write to stdout (echo)
     *
     * @param string|Stringable $output
     * @param bool $lock
     *
     * @return mixed
     *
     * @see OutputConnection::write()
     */
    public function write($output, $lock=false)
    {
        return $this->container->get(OutputConnection::class)->write($output, $lock);
    }

    /**
     * Logs an entry.
     *
     * @param string $level
     * @param string $message
     * @param array  $context (optional)
     *
     * @see LoggerInterface
     */
    public function log(string $level, string $message, array $context = [])
    {
        $this->container->get(LoggerInterface::class)->log($level, $message, $context);
    }
    //</editor-fold>

    //<editor-fold desc="Static Helpers">
    /**
     * This is a static alias to the make (or __invoke) method of the
     * currently running app.
     * Calling it without a parameter returns the app itself.
     *
     * This static method only works after calling boot!!
     *
     * There are some special auto bindings:
     *
     * 'instance' or 'app' returns the Application (this object)
     * 'ioc' or 'container' returns the IOCContainer
     * 'env' returns the current environment (local|production|testing)
     *
     * @param string|null $abstract (optional)
     * @param array       $parameters (optional)
     *
     * @return mixed
     */
    public static function container($abstract=null, array $parameters=[])
    {

        if ($abstract == 'app') {
            return static::$staticInstance;
        }

        if ($abstract == 'env') {
            return static::$staticInstance->environment();
        }

        if ($abstract === null || $abstract == 'container' || $abstract == 'ioc') {
            return static::$staticContainer;
        }

        return static::$staticContainer->__invoke($abstract, $parameters);
    }

    /**
     * This is an alternative to using facades. Just call any static method
     * on this class which does neither exist as an instance method or an static
     * method and the name will get resolved through the container.
     *
     * Application::events() will return $app->get('events')
     *
     * If the object behind this binding supports __invoke(), you can also pass
     * parameters to that method and the method is called.
     *
     * Application::event(1,2) will return $app->get('event')->__invoke(1,2)
     *
     * @param string $name
     * @param array $arguments (optional)
     *
     * @return mixed
     */
    public static function __callStatic($name, array $arguments=[])
    {
        $container = static::$staticContainer;
        $concrete = $container($name);
        if (!is_callable($concrete) && $arguments) {
            throw new LogicException("Passed arguments for a not callable object");
        }
        return $arguments ? $concrete(...$arguments) : $concrete;
    }

    /**
     * @return static|null
     */
    public static function current()
    {
        return static::$staticInstance;
    }

    /**
     * Alias for $this->path()
     *
     * @param string|null             $name
     *
     * @return string|UrlContract
     */
    public static function to($name=null)
    {
        return static::$staticInstance->path($name);
    }

    /**
     * Static alias for $this->config(). Unfortunately we cant use the same
     * method name here...
     *     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public static function setting(string $key, $default=null)
    {
        return static::$staticInstance->config($key, $default);
    }
    //</editor-fold>

    /**
     * Splits the name and path of a path query.
     *
     * @param $name
     *
     * @return array
     */
    protected function splitNameAndPath($name)
    {

        list($start, $end) = strpos($name, '::') ? explode('::', $name) : ['', $name];

        if ($start) {
            return [$start, $end];
        }

        $paths = $this->getPaths();

        // If the name does not exists in the paths, it assumes its a (file)path.
        if (!isset($paths[$end])) {
            return ['', $end];
        }

        return [$end, ''];

    }
}
