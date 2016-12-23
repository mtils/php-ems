<?php

namespace Ems\Contracts\Core;

/**
 * The Extendable interface is for objects which supports __call() methods
 * extendable by your external callables.
 **/
interface Extendable
{
    /**
     * Extend the object with an $extension under $name. If the $extension is a
     * Closure it must be bound to this object (Closure::bindTo($this)) to allow
     * access to invisble methods and property.
     *
     * @param string   $name
     * @param callable $extension
     *
     * @return self
     **/
    public function extend($name, callable $extension);

    /**
     * Return the extension stored under $name.
     *
     *
     * @param string $name
     *
     * @throws \Ems\Contracts\Core\Errors\NotFound If extension not found
     *
     * @return callable
     **/
    public function getExtension($name);

    /**
     * Return the NAMES of all extensions.
     *
     * @return array
     **/
    public function extensions();
}
