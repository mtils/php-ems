<?php

namespace Ems\Contracts\Core;

interface Extractor
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
