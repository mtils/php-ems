<?php


namespace Ems\Core;

use Ems\Contracts\Core\IOCContainer as ContainerContract;
use Ems\Core\Support\IOCContainerProxyTrait;
use Ems\Core\Patterns\SupportListeners;

/**
 * This application is a minimal version optimized
 * for flexibility and less dependency producing
 * Somewhere in you app directory make this:
 *
 * $app = (new Application)->setName('My App')
 *                         ->setVersion('2.0.3')
 *                         ->setPath(realpath('../'))
 **/
class Application implements ContainerContract
{

    use IOCContainerProxyTrait;

    use SupportListeners;

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

    public function __construct($path, ContainerContract $container=null)
    {
        $this->setPath($path);
        $container = $container ?: new IOCContainer;
        $this->setContainer($container);
        $this->container->instance('app', $this);
    }


    /**
     * Return the application name
     *
     * @return string
     **/
    public function name()
    {
        return $this->name;
    }

    /**
     * Set the application name
     *
     * @param string $name
     * @return self
     **/
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Return the application version
     *
     * @return string
     **/
    public function version()
    {
        return $this->version;
    }

    /**
     * Set the application version
     *
     * @param string
     * @return self
     **/
    public function setVersion($version)
    {
        $this->version = $version;
        return $this;
    }

    /**
     * Return the path where the app lives, this is
     * typically your domain root with a public folder
     *
     * @return string
     **/
    public function path()
    {
        return $this->path;
    }

    /**
     * Set the path where the app lives
     *
     * @param string (optional)
     * @return string
     **/
    public function setPath($path)
    {
        $this->path = $path;
        return $this;
    }

    /**
     * Return the root url of the application
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
     * Set the url of the application
     *
     * @param string $url
     * @return self
     **/
    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    /**
     * If you have a multi Domain system there is no static url
     * so you can assign a callable to return it
     * 
     * @param callable $urlProvider
     * @return self
     **/
    public function provideUrl(callable $urlProvider)
    {
        $this->urlProvider = $urlProvider;
        return $this;
    }

    public function boot()
    {
        $this->callListeners('booting', $this);
        $this->callListeners('booted', $this);
    }

    public function booting(callable $listener)
    {
        return $this->storeListener('booting', $listener);
    }

    public function booted(callable $listener)
    {
        return $this->storeListener('booted', $listener);
    }

}
