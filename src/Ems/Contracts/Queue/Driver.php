<?php
/**
 *  * Created by mtils on 23.01.18 at 10:27.
 **/

namespace Ems\Contracts\Queue;

interface Driver
{

    /**
     * Create a job entry inside the queue. The returned job has to have a
     * filled id and status.
     *
     * @param Job   $job
     * @param string   $queueName (optional)
     *
     * @return string (The Job state)
     */
    public function push(Job $job, $queueName='');

    /**
     * Return how many jobs are currently in the queue.
     *
     * @param string $queueName (optional)
     *
     * @return int
     */
    public function count($queueName='');

    /**
     * Get the next job from the queue and remove it.
     *
     * @param string $queueName (optional)
     *
     * @return Job
     */
    public function pop($queueName='');

    /**
     * Return all jobs which are in the queue. This includes failed jobs, queued
     * and running jobs. If finished jobs are
     *
     * @param string $queueName
     *
     * @return Job[]
     */
    public function all($queueName='');
}