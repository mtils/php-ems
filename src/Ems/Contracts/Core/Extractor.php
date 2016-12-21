<?php

namespace Ems\Contracts\Core;

/**
 * The Extractor is used to get object values and its type. The type must also
 * be extracted if the object is not instantiated.
 * The extendable interface is used to extend the object by callables to extract
 * the type of a property. Your callable dont have to support nested paths.
 **/
interface Extractor extends Extendable
{
    /**
     * Return the value of $path relative to $root.
     *
     * @example Extractor::value($user, 'address.state.name')
     *
     * @param mixed  $root (object|array|classname)
     * @param string $path
     *
     * @return mixed
     **/
    public function value($root, $path);

    /**
     * Return the type of $root or of $path relative to $root.
     * Type is a classname or the return value of gettype($x).
     *
     * @example Extractor::type($user, 'address.state');
     *
     * @param mixed  $root
     * @param string $path (optional)
     *
     * @return string
     **/
    public function type($root, $path = null);
}
