<?php
/**
 *  * Created by mtils on 06.02.2022 at 06:12.
 **/

namespace Ems\Contracts\Routing\Exceptions;

use Throwable;
use RuntimeException;

class HttpStatusException extends RuntimeException
{
    /**
     * @var int
     */
    protected $status = 0;

    public function __construct(int $status, $message = "", $code = 0, Throwable $previous = null)
    {
        $this->status = $status;
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return int
     */
    public function getStatus() : int
    {
        return $this->status;
    }

    /**
     * @param int $status
     * @return $this
     */
    public function setStatus(int $status) : HttpStatusException
    {
        $this->status = $status;
        return $this;
    }
}