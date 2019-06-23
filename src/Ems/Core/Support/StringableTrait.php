<?php

namespace Ems\Core\Support;

use Ems\Contracts\Core\StringableTrait as NewStringableTrait;

/**
 * @see \Ems\Contracts\Core\Stringable
 * @deprecated use Ems\Contracts\Core\StringableTrait (was moved)
 **/
trait StringableTrait
{
    use NewStringableTrait; // and not this one ;-)
}
