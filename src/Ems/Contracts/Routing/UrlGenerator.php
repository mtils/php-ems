<?php

namespace Ems\Contracts\Routing;

use Ems\Contracts\Core\Entity;
use Ems\Contracts\Core\Url;

/**
 * The Url Generator generates urls to parts of your application
 * It returns url objects so every extra parameter, path segment, fragment
 * etc. can be set on the returned object instead of passing them to the
 * method.
 **/
interface  UrlGenerator
{
    /**
     * The to method accepts all kind of parameters. Passing an Entity
     * results in self::resource($path, 'show')
     * Passing a string will be used as path.
     *
     * @param string|object $path
     * @param string|RouteScope|null $scope
     *
     * @return Url
     **/
    public function to($path, $scope=null) : Url;

    /**
     * This is similar to laravels UrlGenerator::route() method but also returns
     * an object.
     *
     * @param string $name
     * @param array  $parameters (optional)
     * @param string|RouteScope|null $scope
     *
     * @return Url
     **/
    public function route(string $name, array $parameters = [], $scope=null) : Url;

    /**
     * Return the url to an entity action. Default action is show. Pass an array
     * for a path: resource([$user, 'addresses'])
     *
     * @param object|array  $entity
     * @param string        $action   (optional)
     * @param string|RouteScope|null $scope
     *
     * @return Url
     **/
    public function entity($entity, string $action = 'show', $scope=null) : Url;

    /**
     * Return the url to an asset.
     *
     * @param string $path
     * @param string|RouteScope|null $scope
     *
     * @return Url
     **/
    public function asset(string $path, $scope=null) : Url;
}
