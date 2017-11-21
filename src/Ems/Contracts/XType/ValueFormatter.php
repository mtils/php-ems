<?php

namespace Ems\Contracts\XType;

interface ValueFormatter
{
    /**
     * Builds a XType out of a config. Config can be anything.
     *
     * @param XType  $type
     * @param mixed  $value
     * @param string $view
     * @param string $lang (optional)
     *
     * @return string
     **/
    public function format(XType $type, $value, $view = 'default', $lang = null);

    /**
     * Extend the formatter with a callable. The ValueFormatter
     * searches by class inheritance if a custom $callable was added
     * an let this callable format the value.
     *
     * @param string   $typeClass
     * @param callable $callable
     *
     * @return self
     **/
    public function extend($typeClass, callable $callable);
}
