<?php
/**
 *  * Created by mtils on 09.07.2022 at 08:19.
 **/

namespace Ems\Testing;

use function print_r;

class Debug
{
    public static function exit(...$args)
    {
        self::dump(...$args);
        die();
    }

    public static function dump(...$args)
    {
        foreach ($args as $arg) {
            print_r($arg);
        }
    }
}