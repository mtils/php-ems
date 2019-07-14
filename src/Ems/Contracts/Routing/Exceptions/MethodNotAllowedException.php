<?php
/**
 *  * Created by mtils on 06.07.19 at 19:19.
 **/

namespace Ems\Contracts\Routing\Exceptions;


use Ems\Contracts\Core\Errors\UnSupported;
use RuntimeException;

class MethodNotAllowedException extends RuntimeException  implements UnSupported
{
    //
}