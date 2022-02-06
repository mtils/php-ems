<?php
/**
 *  * Created by mtils on 06.07.19 at 19:19.
 **/

namespace Ems\Contracts\Routing\Exceptions;


use Ems\Contracts\Core\Errors\UnSupported;
use Ems\Contracts\Http\Status;
use Throwable;

class MethodNotAllowedException extends HttpStatusException implements UnSupported
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct(Status::METHOD_NOT_ALLOWED, $message, $code, $previous);
    }
}