<?php

namespace Ems\Core;

class Helper
{
    /**
     * Call a callable (faster)
     *
     * @param callable $callable
     * @param array    $args
     *
     * @return mixed
     **/
    public static function call(callable $callable, $args=[])
    {
        if (!is_array($args)) {
            $args = [$args];
        }

        switch (count($args)) {
            case 0:
                return call_user_func($callable);
            case 1:
                return call_user_func($callable, $args[0]);
            case 2:
                return call_user_func($callable, $args[0], $args[1]);
            case 3:
                return call_user_func($callable, $args[0], $args[1], $args[2]);
            case 4:
                return call_user_func($callable, $args[0], $args[1], $args[2], $args[3]);
        }

        return call_user_func_array($callable, $args);
    }
}
