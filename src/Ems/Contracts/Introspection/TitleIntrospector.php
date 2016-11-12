<?php

namespace Ems\Contracts\Introspection;

/**
 * A TitleIntrospector helps to display objects in an interface
 * Instead of manually ask a translator or writing the names ob object properties
 * in a form or template this interface will assure you can get the titles from
 * somewhere.
 **/
interface TitleIntrospector
{
    /**
     * Returns a readable title of an resource property.
     *
     * @param string|object $resource The resourcename or an object of it
     * @param string        $path     A key name. Can be dotted like address.street.name
     *
     * @return string The readable title
     **/
    public function keyTitle($resource, $path);

    /**
     * Returns a readable title of an resource instance or instances of type $resource.
     *
     * @param string|object $resource The resource or an object of it
     * @param int           $quantity (optional) The quantity (for singular/plural)
     *
     * @return string A readable title of this object
     **/
    public function resourceTitle($resource, $quantity = 1);

    /**
     * Returns a readable title of an enum value of property $key of class $class.
     *
     * @param string|object $resource The resource or an object of it
     * @param string        $path     A key name. Can be dotted like address.street.name
     * @param string An enum value. Preferable strings to allow easy translations
     *
     * @return string A readable title of this enum value
     **/
    public function enumTitle($resource, $path, $value);
}
