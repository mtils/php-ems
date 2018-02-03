<?php
/**
 *  * Created by mtils on 26.01.18 at 18:12.
 **/

namespace Ems\Queue;


use DateTime;

trait JobConfiguration
{
    /**
     * @var array
     */
    protected $attributes = [];

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
    public function at($delay)
    {
        $absolute = is_string($delay) ? (new DateTime())->modify($delay) : $delay;
        return $this->forkWith('plannedStart', $absolute);
    }

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
    public function tries($tries)
    {
        return $this->forkWith('maxTries', $tries);
    }

    /**
     * How many seconds is the job allowed to run.
     *
     * @example self::timeout(3600)->run();
     *
     * @param int $timeout
     *
     * @return self
     */
    public function timeout($timeout)
    {
        return $this->forkWith('timeout', $timeout);
    }

    /**
     * Push the job on an specific channel.
     *
     * @example self::onChannel($channel)->run()
     *
     * @param string $channel
     *
     * @return self
     */
    public function onChannel($channel)
    {
        return $this->forkWith('channelName', $channel);
    }

    /**
     * Create a new fork with the given job attribute
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return $this
     */
    protected function forkWith($key, $value)
    {
        $attributes = $this->attributes;
        $attributes[$key] = $value;
        return $this->fork($attributes);
    }
}