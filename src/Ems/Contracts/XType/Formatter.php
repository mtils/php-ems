<?php

namespace Ems\Contracts\XType;

use Ems\Contracts\Core\Extendable;

/**
 * Interface Formatter
 *
 * This is the XType counterpart of Core\Formatter. You can pass an xtype and
 * the value gets formatted by the type.
 *
 * The extendable interface works with classes, not strings. So if you extend it
 * for class Core\UnitType, every derived class will use that extension too.
 *
 * @package Ems\Contracts\XType
 */
interface Formatter extends Extendable
{
    /**
     * Format the $path of $object for $view in $lang.
     * This Formatter has to support $paths of properties like "title" but also
     * nested like $user, 'address.street'.
     *
     * @param object $object
     * @param string $path   The path. Can be a property name or a nested path
     * @param string $view   (optional)
     *
     * @return string
     **/
    public function format($object, $path, $view = 'default');

    /**
     * Format a single value by passing the XType by your own.
     *
     * @param XType  $type
     * @param mixed  $value
     * @param string $view
     *
     * @return string
     **/
    public function value(XType $type, $value, $view = 'default');

}
