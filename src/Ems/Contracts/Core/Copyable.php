<?php

namespace Ems\Contracts\Core;

/**
 * The copyable interface is for all objects which support to
 * to return a clone of itself. Optionally pass attributes to
 * change the passed attributes on the copy.
 * What this means can be deceided per use case.
 * A copyable router would work like laravels $router->group.
 * You could pass a namespace offset for the controllers and
 * set different variables which would apply on every route() call
 * to not have to copy the variables on every call.
 * (Laravel solves this different with a callable which is
 * handy for the routes.php file).
 *
 * Another example is Ems\Assets\Manager. You can pass a group
 * prefix and pass the altered Manager to all classes of a
 * distinct package and the classes wont rekognize that they
 * dont use a prefix.
 *
 * Some classes (basicly intended singletons or immutables)
 * use a Proxy which calls the parent attributes, others just
 * return a new object. Its up to you, this interface just
 * assures a common syntax for this pattern
 **/
interface Copyable
{
    /**
     * Return a new instance of this object with the
     * passed $attributes changed. Mostly the attributes
     * are the name of setters without "set" in lowercase
     * but dont have to.
     * A configurable object (Ems\Contracts\Core\Configurable)
     * would possibly set their options.
     *
     * @param array $attributes
     *
     * @return self
     **/
    public function replicate(array $attributes = []);
}
