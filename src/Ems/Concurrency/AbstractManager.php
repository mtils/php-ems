<?php
/**
 *  * Created by mtils on 08.09.19 at 11:55.
 **/

namespace Ems\Concurrency;


use DateTime;
use Ems\Contracts\Concurrency\Exceptions\PlannedTimeOverflowException;
use Ems\Contracts\Concurrency\Exceptions\ReleaseException;
use Ems\Contracts\Concurrency\Handle;
use Ems\Contracts\Concurrency\Manager;
use Ems\Core\Lambda;
use Throwable;
use function print_r;
use function usleep;
use const LOCK_EX;
use const LOCK_NB;

abstract class AbstractManager implements Manager
{
    /**
     * @var int
     */
    protected $tries = 1;

    /**
     * The delay between tries in milliseconds,
     *
     * @var int
     */
    protected $retryDelay = 200;

    /**
     * Create a new instance of your class with retry parameter assigned.

     * @param int   $tries
     * @param int   $delay
     * @param array $attributes (optional)
     * @return static
     */
    abstract protected function replicate($tries, $delay, array $attributes=[]);

    /**
     * Perform the next lock by retrying it $times with a pause of $delay
     * milliseconds between the tries.
     *
     * @param int $times
     * @param int $delay
     *
     * @return self
     * @example $manager->retry(5)->lock()
     *
     */
    public function retry($times = 3, $delay = 200)
    {
        return $this->replicate($times, $delay);
    }

    /**
     * {@inheritDoc}
     *
     * @param callable $run
     * @param int $timeout (default:0)
     *
     * @return mixed
     * @throws \ReflectionException
     * @throws ReleaseException
     * @throws Throwable
     */
    public function run(callable $run, $timeout = null)
    {
        $uri = Lambda::cacheId($run);

        $handle = $this->lock($uri, $timeout);
        $runResult = null;
        $runException = null;

        // Try to run the callable
        try {

            $result = $run();
            return $this->cleanUpAndFinish($handle, $result);

        } catch (Throwable $e) { // If the callable did throw an exception, remember it

            $runException = $e;
            throw $e;

        } finally {

            return $this->cleanUpAndFinish($handle, $runResult, $runException);

        }
    }

    /**
     * {@inheritDoc}
     *
     * @param callable $run
     *
     * @return self
     */
    public function when(callable $run)
    {
        // TODO: Implement when() method.
    }

    /**
     * Performs the retry loop. Pass a callable to actually acquire the lock.
     * Everything casted to true will be interpreted as a successful lock. The
     * result will be returned to your method.
     *
     * @param callable $lockFunction
     *
     * @return mixed
     */
    protected function loop(callable $lockFunction)
    {
        $sleepTime = $this->retryDelay*1000;

        for ($i=0; $i<$this->tries; $i++) {
            if ($result = $lockFunction($i)) {
                return $result;
            }
            usleep($sleepTime);
        }
    }

    /**
     * Use this method at the end of your release method to throw an exception
     * if the lifetime of your job exceeded. This means the lock was away at the
     * end of your job execution.
     *
     * @param Handle   $handle
     * @param DateTime $now (optional)
     *
     * @throws \Exception
     */
    protected function failIfTtlExceeded(Handle $handle, DateTime $now=null)
    {
        if (!$handle->validUntil) {
            return;
        }

        $now = $now ?: new DateTime();

        if ($now <= $handle->validUntil) {
            return;
        }

        $expiry = $handle->validUntil->format('Y-m-d H:i:s');
        throw new PlannedTimeOverflowException("The lock for $handle->uri expired at $expiry. Other processes may had access since then.");
    }
    /**
     * @param string  $uri
     * @param string $token
     * @param int    $ttl (optional)
     *
     * @return Handle
     *
     * @throws \Exception
     */
    protected function createHandle($uri, $token, $ttl=null)
    {
        $validUntil = null;
        if ($ttl !== null) {
            $validUntil = new DateTime();
            $endTimestamp = $validUntil->getTimestamp() + $ttl/1000;
            $validUntil->setTimestamp($endTimestamp);
        }

        return new Handle($uri, $token, $validUntil);

    }

    /**
     * Release the lock and if it didnt work, add any information to a special
     * exception and throw it.
     *
     * @param Handle         $handle
     * @param mixed          $runResult
     * @param Throwable|null $runException
     *
     * @return mixed
     */
    protected function cleanUpAndFinish(Handle $handle, $runResult, Throwable $runException=null)
    {
        try {

            $this->release($handle);
            return $runResult;

        } catch (ReleaseException $e) {
            //
        }

        $e->setRunResult($runResult);

        if ($runException) {
            $e->setRunException($runException);
        }

        throw $e;

    }
}