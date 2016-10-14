<?php


namespace Ems\Contracts\Introspection;

/**
 * A DescriptionIntrospector allows to get descriptions for internal objects
 * @see \Ems\Contracts\Introspection\TitleIntrospector
 **/
interface DescriptionIntrospector
{

    /**
     * Returns a readable description of an resource property.
     *
     * @param string|object $resource The resource or an object of it
     * @param string $path A key name. Can be dotted like address.street.name
     * @return string The readable title
     **/
    public function keyDescription($resource, $path);

    /**
     * Returns a readable description of an instance or instances of $resource
     *
     * @param string|object $resource The class or an object of it
     * @param int $quantity (optional) The quantity (for singular/plural)
     * @return string A readable title of this object
     **/
    public function resourceDescription($resource, $quantity=1);

}