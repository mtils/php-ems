<?php


namespace Ems\Contracts\Core;


interface Stringable
{

    /**
     * Renders this object. You cant throw exceptions in a
     * __toString method.
     *
     * @return string
     **/
    public function __toString();

    /**
     * When an error occures call this handler. Because php doesnt
     * allow exceptions in __toString your object has to provide
     * this method to allow logging of errors
     * The exception and this object will be passed to the callable
     *
     * @param callable $handler
     * @return self
     **/
    public function onError(callable $handler);
}
