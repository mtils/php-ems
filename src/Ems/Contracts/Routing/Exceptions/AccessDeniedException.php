<?php
/**
 *  * Created by mtils on 06.02.2022 at 06:19.
 **/

namespace Ems\Contracts\Routing\Exceptions;

use Ems\Contracts\Http\Status;
use Throwable;

class AccessDeniedException extends HttpStatusException
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct(Status::FORBIDDEN, $message, $code, $previous);
    }
}