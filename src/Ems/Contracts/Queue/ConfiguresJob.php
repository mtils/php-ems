<?php
/**
 *  * Created by mtils on 26.01.18 at 17:57.
 **/

namespace Ems\Contracts\Queue;

/**
 * Interface ConfiguresJob
 *
 * This is just a common interface for Queue and Tasker.
 *
 * @package Ems\Contracts\Queue
 */
interface ConfiguresJob
{

    /**
     * Push a job later. DateTime means an exact time, string is a DateTime->modify()
     * string.
     *
     * @example self::at('+1 hour')->run()
     *
     * @param \DateTime|string $delay
     *
     * @return self
     */
    public function at($delay);

    /**
     * Determine the amount of tries the job has to be started.
     * Default is just one try.
     *
     * @example self::tries(5)->run()
     *
     * @param int $tries
     *
     * @return self
     */
    public function tries($tries);

    /**
     * How many seconds is the job allowed to run.
     *
     * @example self::timeout(3600)->run();
     *
     * @param int $timeout
     *
     * @return self
     */
    public function timeout($timeout);

    /**
     * Push the job on an specific channel.
     *
     * @example self::onChannel($channel)->run()
     *
     * @param string $channel
     *
     * @return self
     */
    public function onChannel($channel);

}