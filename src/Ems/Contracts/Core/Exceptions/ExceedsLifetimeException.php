<?php
/**
 *  * Created by mtils on 06.02.18 at 05:19.
 **/

namespace Ems\Contracts\Core\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Class ExceedsLifetimeException
 *
 * This exception is thrown every time a class estimates a duration
 * that will not be guaranteed to be completed within the the current
 * request.
 * This will allow to defer the operation to a queue or bus or other
 * machine.
 *
 * @package Ems\Contracts\Core\Exceptions
 */
class ExceedsLifetimeException extends RuntimeException
{
    /**
     * @var string|array
     */
    protected $operation;

    /**
     * @var array
     */
    protected $operationArguments = [];

    /**
     * ExceedsLifetimeException constructor.
     *
     * @param string|array $operation
     * @param array        $operationArgs (optional)
     * @param string       $message (optional)
     * @param Exception    $previous (optional)
     */
    public function __construct($operation, $operationArgs=[], $message = "", $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->operation = $operation;
        $this->operationArguments = $operationArgs;
    }

    /**
     * Return the operation that was planned to be processed (but could'nt).
     *
     * @return array|string
     */
    public function operation()
    {
        return $this->operation;
    }

    /**
     * Return the arguments for that operation.
     *
     * @return array
     */
    public function operationArguments()
    {
        return $this->operationArguments;
    }
}