<?php
/**
 *  * Created by mtils on 24.01.18 at 10:29.
 **/

namespace Ems\Contracts\Queue;
use Ems\Core\Exceptions\KeyNotFoundException;
use function property_exists;

/**
 * Class Job
 *
 * A job is something to communicate with a queue.
 * There is no interface for it because the complete system is build to NOT write
 * queue jobs.
 *
 * A Controller for everything, a command for the same and then Jobs for the same?
 * Better encapsulate your code into self-describing classes that you can
 * directly call.
 *
 * The job is mostly internally used to give an operation into a queue. Normally
 * you should not come into a situation to create or instantiate jobs at all.
 *
 * @package Ems\Contracts\Queue
 */
class Job
{
    /**
     * @var int|string
     */
    protected $taskId;

    /**
     * @var string|callable
     */
    protected $operation;

    /**
     * @var array
     */
    protected $arguments = [];

    /**
     * @var int
     */
    protected $maxTries = 10;

    /**
     * @var int
     */
    protected $attempts = 0;

    /**
     * @var int
     */
    protected $timeout = 0;

    /**
     * @var \DateTime
     */
    protected $plannedStart;

    /**
     * @var string
     */
    protected $channelName;

    /**
     * @var string
     */
    protected $state;

    /**
     * Job constructor.
     *
     * @param array $attributes (optional)
     */
    public function __construct(array $attributes=[])
    {
        $this->fill($attributes);
    }

    /**
     * {@inheritdoc}
     *
     * @return string|int
     */
    public function taskId()
    {
        return $this->taskId;
    }

    /**
     * @param int|string $taskId
     *
     * @return $this
     */
    public function setTaskId($taskId)
    {
        $this->taskId = $taskId;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return string|callable
     */
    public function operation()
    {
        return $this->operation;
    }

    /**
     * @param callable|string $operation
     *
     * @return $this
     */
    public function setOperation($operation)
    {
        $this->operation = $operation;
        return $this;
    }


    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function arguments()
    {
        return $this->arguments;
    }

    /**
     * @param array $arguments
     *
     * @return $this
     */
    public function setArguments(array $arguments)
    {
        $this->arguments = $arguments;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return int
     */
    public function maxTries()
    {
        return $this->maxTries;
    }

    /**
     * @param int $maxTries
     *
     * @return $this;
     */
    public function setMaxTries($maxTries)
    {
        $this->maxTries = $maxTries;
        return $this;
    }


    /**
     * Return the amount of attempts
     *
     * @return int
     */
    public function attempts()
    {
        return $this->attempts;
    }

    /**
     * @param int $attempts
     *
     * @return $this
     */
    public function setAttempts($attempts)
    {
        $this->attempts = $attempts;
        return $this;
    }

    /**
     * Return how many seconds the job is allowed to run.
     *
     * @return int
     */
    public function timeout()
    {
        return $this->timeout;
    }

    /**
     * @param int $timeout
     *
     * @return $this
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function plannedStart()
    {
        return $this->plannedStart;
    }

    /**
     * @param \DateTime $plannedStart
     *
     * @return $this
     */
    public function setPlannedStart(\DateTime $plannedStart)
    {
        $this->plannedStart = $plannedStart;
        return $this;
    }


    /**
     * @return string
     */
    public function channelName()
    {
        return $this->channelName;
    }

    /**
     * @param string $queueName
     *
     * @return $this
     */
    public function setChannelName($queueName)
    {
        $this->channelName = $queueName;
        return $this;
    }

    /**
     * Return the queue state of this job.
     *
     * @see Queue::RUNNING ...
     *
     * @return string
     */
    public function state()
    {
        return $this->state;
    }

    /**
     * Set the (queue) state of this job.
     *
     * @see Queue::RUNNING ...
     *
     * @param string $state
     *
     * @return $this
     */
    public function setState($state)
    {
        $this->state = $state;
        return $this;
    }

    /**
     * Fill the job with the passed attributes.
     *
     * @param array $attributes
     *
     * @return $this
     */
    public function fill(array $attributes)
    {

        foreach ($attributes as $key=>$value) {
            if (!property_exists($this, $key)) {
                throw new KeyNotFoundException("Key $key is not known as a job property.");
            }
            $this->$key = $value;
        }

        return $this;
    }

}