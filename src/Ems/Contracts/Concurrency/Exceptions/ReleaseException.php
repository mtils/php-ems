<?php
/**
 *  * Created by mtils on 08.09.19 at 07:54.
 **/

namespace Ems\Contracts\Concurrency\Exceptions;


use Ems\Contracts\Concurrency\ConcurrencyError;
use Ems\Core\Exceptions\DataIntegrityException;
use Throwable;

/**
 * Class ReleaseException
 *
 * This Exception is thrown if the manager is unable to release a lock. This will
 * lead to a deadlock so do not ignore it.
 *
 * @package Ems\Contracts\Concurrency\Exceptions
 */
class ReleaseException extends DataIntegrityException implements ConcurrencyError
{
    /**
     * @var mixed
     */
    protected $runResult;

    /**
     * @var Throwable
     */
    protected $runException;

    /**
     * Return the result of the callable you passed to run()
     *
     * @return mixed
     */
    public function getRunResult()
    {
        return $this->runResult;
    }

    /**
     * Set the result of a run() callable.
     *
     * @param mixed $result
     *
     * @return $this
     */
    public function setRunResult($result)
    {
        $this->runResult = $result;
        return $this;
    }

    /**
     * Return the exception thrown by the callable in run().
     *
     * @return Throwable
     */
    public function getRunException()
    {
        return $this->runException;
    }

    /**
     * @param Throwable $runException
     *
     * @return ReleaseException
     */
    public function setRunException(Throwable $runException)
    {
        $this->runException = $runException;
        return $this;
    }

}