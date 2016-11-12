<?php

namespace Ems\Contracts\Routing;

use Ems\Contracts\Core\Entity;

/**
 * The Url Generator generates urls to parts of your application
 * It returns url objects so every extra parameter, path segment, fragment
 * etc can be setted on the returned object instead of passing the to the
 * method.
 **/
interface UrlGenerator
{
    /**
     * The to method accepts all kind of parameters. Passing an Entity
     * results in self::resource($path, 'show')
     * Passing a string will be used as path.
     *
     * @param string $path
     * @param bool   $absolute (optional)
     * @param bool   $secure   (optional)
     *
     * @return \Ems\Contracts\Core\Url
     **/
    public function to($path, $absolute = null, $secure = null);

    /**
     * This is similar to laravels UrlGenerator::route() method but also returns
     * an object.
     *
     * @param string $name
     * @param array  $parameters (optional)
     * @param bool   $absolute   (optional)
     * @param bool   $secure     (optional)
     *
     * @return \Ems\Contracts\Core\Url
     **/
    public function route($name, $parameters = [], $absolute = null, $secure = null);

    /**
     * Return the url to an entity action. Default action is show.
     *
     * @param \Ems\Contracts\Core\Entity $entity
     * @param string                     $action   (optional)
     * @param bool                       $absolute (optional)
     * @param bool                       $secure   (optional)
     *
     * @return \Ems\Contracts\Core\Url
     **/
    public function resource(Entity $entity, $action = 'show', $absolute = null, $secure = null);

    /**
     * Return the url to an asset.
     *
     * @param string $asset
     * @param bool   $absolute (optional)
     * @param bool   $secure   (optional)
     *
     * @return \Ems\Contracts\Core\Url
     **/
    public function asset($path, $absolute = null, $secure = null);
}
