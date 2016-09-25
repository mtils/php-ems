<?php


namespace Ems\Core;

use Ems\Contracts\Core\PathFinder as PathFinderContract;
use Ems\Contracts\Core\AppPath as AppPathContract;
use OutOfBoundsException;
use Exception;

class PathFinderProxy implements PathFinderContract
{

    /**
     * @var \Ems\Contracts\Core\PathFinder
     **/
    protected $parent;

    /**
     * @var string
     **/
    protected $namespace = '';


    public function __construct(PathFinderContract $parent, $namespace='')
    {
        $this->parent = $parent;
        $this->namespace = $namespace;
    }

    /**
     * {@inheritdoc}
     *
     * @param string
     * @return \Ems\Contracts\Core\AppPath
     **/
    public function to($scope)
    {
        return $this->parent->to($this->translate($scope));
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
        return $this->parent->map($this->translate($scope), $path, $url);
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     **/
    public function scopes()
    {
        if (!$this->namespace) {
            return $this->parent->scopes();
        }
        return array_values(array_filter($this->parent->scopes(), function($scope) {
            return strpos($scope, $this->namespace."::") === 0;
        }));
    }

    /**
     * {@inheritdoc}
     *
     * @param string
     * @return self
     **/
    public function namespaced($namespace)
    {
        return $this->parent->namespaced($namespace);
    }

    /**
     * {@inheritdoc}
     *
     * @param string
     * @return string
     **/
    public function relative($url)
    {
        return $this->parent->relative($url);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $path (optional)
     * @return string
     **/
    public function absolute($relativePath=null)
    {
        return $this->parent->absolute($relativePath);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $path (optional)
     * @return string
     **/
    public function url($path=null)
    {
        return $this->parent->url($path);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     **/
    public function __toString()
    {
        try {
            return $this->parent->__toString();
        } catch (Exception $e) {
            return '';
        }
    }

    /**
     * Translates the scope into the namespaced one
     *
     * @param string $scope
     * @return string
     **/
    protected function translate($scope)
    {
        return $this->namespace ? $this->namespace . "::$scope" : $scope;
    }

}
