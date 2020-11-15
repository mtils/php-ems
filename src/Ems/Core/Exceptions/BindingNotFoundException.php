<?php
/**
 *  * Created by mtils on 14.11.20 at 11:30.
 **/

namespace Ems\Core\Exceptions;


use Ems\Contracts\Core\Errors\NotFound;
use Psr\Container\NotFoundExceptionInterface;

class BindingNotFoundException extends IOCContainerException implements NotFoundExceptionInterface, NotFound
{
    //
}