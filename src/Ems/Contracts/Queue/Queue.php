<?php
/**
 *  * Created by mtils on 23.01.18 at 10:27.
 **/

namespace Ems\Contracts\Queue;

/**
 * Interface Queue
 *
 * The Queue is the central place to defer your jobs.
 * Unlike the most other frameworks the ems queue force you to NOT write jobs.
 * run($operation) is typically a class and a method.
 * Closures cannot be serialized so I do not try to make special workarounds
 * like SuperClosure. You should just pass a normal class and method of your
 * code.
 * Why not write custom jobs? Controllers for stuff, commands, jobs, all for
 * the same action? That should not be necessary. If you really think you need
 * to write a job, just call that job. But the queue does not force you to do
 * that.
 *
 * @package Ems\Contracts\Queue
 */
interface Queue extends ConfiguresJob
{

    /**
     * @var string
     */
    const UNKNOWN = 'unknown';

    /**
     * @var string
     */
    const QUEUED = 'queued';

    /**
     * @var string
     */
    const RESERVED = 'reserved';

    /**
     * @var string
     */
    const RUNNING = 'running';

    /**
     * @var string
     */
    const FAILED = 'failed';

    /**
     * @var string
     */
    const FINISHED = 'finished';

    /**
     * @var string
     */
    const PAUSED = 'paused';

    /**
     * Create a job entry inside the queue. Return the status of the created
     * job.
     *
     * @param array|string $operation
     * @param array        $arguments (optional)
     *
     * @return Job
     */
    public function run($operation, array $arguments=[]);

    /**
     * Return how many jobs are currently in the queue.
     *
     * @param string $channel (optional)
     *
     * @return int
     */
    public function count($channel='');

    /**
     * Get the next job from the a queue channel and remove (reserve) it.
     *
     * @param string $channel (optional)
     *
     * @return Job|null
     */
    public function pop($channel='');

    /**
     * Return all jobs which are in the queue. This includes failed jobs, queued
     * and running jobs. If finished jobs are
     *
     * @param string $channel
     *
     * @return Job[]
     */
    public function all($channel='');

    /**
     * Return all channel names.
     *
     * @return string[]
     */
    public function channelNames();

    /**
     * Add a new channel to the queue. A channel is just a special configuration
     * of a queue. It can be just a queue name or a complete connection.
     *
     * @param string $name
     * @param Driver $channel
     *
     * @return self
     */
    public function addChannel($name, Driver $channel);

    /**
     * Remove a previously added channel. (Normally not needed)
     *
     * @param string $name
     *
     * @return self
     */
    public function removeChannel($name);

}