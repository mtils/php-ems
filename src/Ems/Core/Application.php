<?php

namespace Ems\Core;

use Ems\Contracts\Core\HasMethodHooks;
use Ems\Contracts\Core\IOCContainer as ContainerContract;
use Ems\Core\Support\IOCContainerProxyTrait;
use Ems\Core\Patterns\SupportListeners;
use Ems\Core\Patterns\HookableTrait;

/**
 * This application is a minimal version optimized
 * for flexibility and less dependencies.
 * Somewhere in you app directory make this:.
 *
 * $app = (new Application)->setName('My App')
 *                         ->setVersion('2.0.3')
 *                         ->setPath(realpath('../'))
 **/
class Application implements ContainerContract, HasMethodHooks
{
    use IOCContainerProxyTrait;
    use HookableTrait;

    const PRODUCTION = 'production';

    const LOCAL = 'local';

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
     * @var string
     **/
    protected $path;

    /**
     * @var string
     **/
    protected $url;

    /**
     * @var string
     **/
    protected $urlProvider;

    /**
     * @var string
     */
    protected $environment = self::PRODUCTION;

    /**
     * @var callable
     */
    protected $environmentProvider;

    /**
     * @var bool
     */
    protected $wasBooted = false;

    /**
     * @var static
     */
    protected static $staticInstance;

    /**
     * @var ContainerContract
     */
    protected static $staticContainer;

    public function __construct($path, ContainerContract $container = null)
    {
        $this->setPath($path);
        $container = $container ?: new IOCContainer();
        $this->setContainer($container);
        $this->container->instance('app', $this);
    }

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
    public function setName($name)
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

    /**
     * Return the path where the app lives, this is
     * typically your domain root with a public folder.
     *
     * @return string
     **/
    public function path()
    {
        return $this->path;
    }

    /**
     * Set the path where the app lives.
     *
     * @param string (optional)
     *
     * @return string
     **/
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Return the root url of the application.
     *
     * @return string
     **/
    public function url()
    {
        if ($this->urlProvider) {
            return call_user_func($this->urlProvider, $this);
        }

        return $this->url;
    }

    /**
     * Set the url of the application.
     *
     * @param string $url
     *
     * @return self
     **/
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * If you have a multi Domain system there is no static url
     * so you can assign a callable to return it.
     * 
     * @param callable $urlProvider
     *
     * @return self
     **/
    public function provideUrl(callable $urlProvider)
    {
        $this->urlProvider = $urlProvider;

        return $this;
    }

    /**
     * Boot the application. Whatever this means is only determined by the
     * listeners to onBefore('boot') or onAfter('boot').
     *
     */
    public function boot()
    {
        $this->callBeforeListeners('boot', [$this]);
        $this->callAfterListeners('boot', [$this]);
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
    public function setEnvironment($env)
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

    /**
     * @return array
     */
    public function methodHooks()
    {
        return ['boot'];
    }

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
     * @param string $abstract (optional)
     * @param array  $parameters (optional)
     *
     * @return mixed
     */
    public static function get($abstract=null, array $parameters=[])
    {

        if (!$abstract || $abstract == 'instance' || $abstract == 'app') {
            return static::$staticInstance;
        }

        if ($abstract == 'env') {
            return static::$staticInstance->environment();
        }

        if ($abstract == 'container' || $abstract == 'ioc') {
            return static::$staticContainer;
        }

        return static::$staticContainer->make($abstract, $parameters);
    }

    /**
     * This is an alternative to using facades. Just call any static method
     * on this class which does neither exist as an instance method or an static
     * method and the name will get resolved through the container.
     *
     * Application::events() will return $app->make('events')
     *
     * If the object behind this binding supports __invoke(), you can also pass
     * parameters to that method and the method is called.
     *
     * Application::event(1,2) will return $app->make('event')->__invoke(1,2)
     *
     * @param string $name
     * @param array $arguments (optional)
     *
     * @return mixed
     */
    public static function __callStatic($name, array $arguments=[])
    {
        $concrete = static::get($name);
        return $arguments ? $concrete(...$arguments) : $concrete;
    }
}
