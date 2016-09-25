<?php


namespace Ems\Core;

use Ems\Contracts\Core\PathFinder as PathFinderContract;
use Ems\Contracts\Core\AppPath as AppPathContract;
use OutOfBoundsException;
use Exception;

class PathFinder implements PathFinderContract
{

    /**
     * This is the root namespace, which will be used
     * if you use the AppPath interface of this PathFinder
     **/
    const ROOT = 'app';

    /**
     * @var array
     **/
    protected $paths = [];

    /**
     * @var callable
     **/
    protected $pathCreator;


    public function __construct()
    {
        $this->pathCreator = function ($path, $url) {
            return (new AppPath)->setBasePath($path)
                                ->setBaseUrl($url);
        };
    }

    /**
     * {@inheritdoc}
     *
     * @param string
     * @return \Ems\Contracts\Core\AppPath
     **/
    public function to($scope)
    {
        if (isset($this->paths[$scope])) {
            return $this->paths[$scope];
        }

        throw new OutOfBoundsException("Path of '$scope' was not mapped");
    }

    /**
     * {@inheritdoc}
     *
     * @param string $scope
     * @param string|\Ems\Contracts\Core\AppPath $path
     * @param string $url (optional)
     * @return \Ems\Contracts\Core\AppPath
     **/
    public function map($scope, $path, $url=null)
    {
        if ($path instanceof AppPathContract) {
            $this->paths[$scope] = $path;
            return $path;
        }
        $this->paths[$scope] = $this->createAppPath($path, $url);
        return $this->paths[$scope];
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     **/
    public function scopes()
    {
        return array_keys($this->paths);
    }

    /**
     * {@inheritdoc}
     *
     * @param string
     * @return self
     **/
    public function namespaced($namespace)
    {
        return new PathFinderProxy($this, $namespace);
    }

    /**
     * {@inheritdoc}
     *
     * @param string
     * @return string
     **/
    public function relative($url)
    {
        return $this->to(static::ROOT)->relative($url);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $path (optional)
     * @return string
     **/
    public function absolute($relativePath=null)
    {
        return $this->to(static::ROOT)->absolute($relativePath);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $path (optional)
     * @return string
     **/
    public function url($path=null)
    {
        return $this->to(static::ROOT)->url($path);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     **/
    public function __toString()
    {
        try {
            return $this->to(static::ROOT)->__toString();
        } catch (Exception $e) {
            return '';
        }
    }

    /**
     * Set a custom AppPath creator
     *
     * @param callable
     * @return self
     **/
    public function createPathsBy(callable $creator)
    {
        $this->pathCreator = $creator;
        return $this;
    }

    /**
     * @param string $path
     * @param string $url
     * @return \Ems\Contracts\Core\AppPath
     **/
    protected function createAppPath($path, $url)
    {
        return call_user_func($this->pathCreator, $path, $url);
    }

}
