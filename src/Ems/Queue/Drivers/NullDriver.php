<?php
/**
 *  * Created by mtils on 27.01.18 at 15:46.
 **/

namespace Ems\Queue\Drivers;


use function array_pop;
use Ems\Contracts\Queue\Driver;
use Ems\Contracts\Queue\Job;
use Ems\Contracts\Queue\Queue;

class NullDriver implements Driver
{
    /**
     * @var array
     */
    protected $queues = [];

    /**
     * Create a job entry inside the queue. The returned job has to have a
     * filled id and status.
     *
     * @param Job $job
     * @param string $queueName (optional)
     *
     * @return string (The Job state)
     */
    public function push(Job $job, $queueName = '')
    {
        if (!isset($this->queues[$queueName])) {
            $this->queues[$queueName] = [];
        }

        $this->queues[$queueName][] = $job;

        return Queue::QUEUED;

    }

    /**
     * Return how many jobs are currently in the queue.
     *
     * @param string $queueName (optional)
     *
     * @return int
     */
    public function count($queueName = '')
    {
        if (isset($this->queues[$queueName])) {
            return count($this->queues[$queueName]);
        }
        return 0;
    }

    /**
     * Get the next job from the queue and remove it.
     *
     * @param string $queueName (optional)
     *
     * @return Job|null
     */
    public function pop($queueName = '')
    {

        if (!isset($this->queues[$queueName])) {
            return null;
        }

        if (!$this->queues[$queueName]) {
            return null;
        }

        return array_pop($this->queues[$queueName]);
    }

    /**
     * Return all jobs which are in the queue. This includes failed jobs, queued
     * and running jobs. If finished jobs are
     *
     * @param string $queueName
     *
     * @return Job[]
     */
    public function all($queueName = '')
    {
        if (isset($this->queues[$queueName])) {
            return $this->queues[$queueName];
        }
        return [];
    }

}