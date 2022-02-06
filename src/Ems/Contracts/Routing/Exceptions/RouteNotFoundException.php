<?php
/**
 *  * Created by mtils on 06.07.19 at 19:19.
 **/

namespace Ems\Contracts\Routing\Exceptions;


use Ems\Contracts\Core\Errors\NotFound;
use Ems\Contracts\Http\Status;
use Throwable;

class RouteNotFoundException extends HttpStatusException implements NotFound
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct(Status::NOT_FOUND, $message, $code, $previous);
    }

}