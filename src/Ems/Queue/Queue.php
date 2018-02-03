<?php
/**
 *  * Created by mtils on 26.01.18 at 18:02.
 **/

namespace Ems\Queue;

use Ems\Contracts\Queue\Driver;
use Ems\Contracts\Queue\Job;
use Ems\Contracts\Queue\Queue as QueueContract;
use Ems\Core\Exceptions\ResourceNotFoundException;

class Queue implements QueueContract
{
    use JobConfiguration;

    /**
     * @var array
     */
    protected $channels = [];

    /**
     * @var \Closure
     */
    protected $proxyPusher;

    /**
     * @var string
     */
    public static $defaultChannel = 'default';

    /**
     * Queue constructor.
     *
     * @param Driver $defaultChannel
     */
    public function __construct(Driver $defaultChannel)
    {
        $this->addChannel(static::$defaultChannel, $defaultChannel);
        $this->proxyPusher = function (Job $job) {
            return $this->pushToChannel($job);
        };
    }

    /**
     * {@inheritdoc}
     *
     * @param array|string $operation
     * @param array        $arguments (optional)
     *
     * @return Job
     */
    public function run($operation, array $arguments=[])
    {
        $job = $this->createJob($this->attributes, $operation, $arguments);
        return $this->pushToChannel($job);
    }

    /**
     * {@inheritdoc}
     *
     * @return string[]
     */
    public function channelNames()
    {
        return array_keys($this->channels);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $channel (optional)
     *
     * @return int
     */
    public function count($channel = '')
    {
        $channel = $channel ?: static::$defaultChannel;
        return $this->channelOrFail($channel)->count($channel);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $channel (optional)
     *
     * @return Job
     */
    public function pop($channel = '')
    {
        $channel = $channel ?: static::$defaultChannel;
        return $this->channelOrFail($channel)->pop($channel);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $channel
     *
     * @return Job[]
     */
    public function all($channel = '')
    {
        $channel = $channel ?: static::$defaultChannel;
        return $this->channelOrFail($channel)->all($channel);
    }


    /**
     * {@inheritdoc}
     *
     * @param string $name
     * @param Driver $channel
     *
     * @return self
     */
    public function addChannel($name, Driver $channel)
    {
        $this->channels[$name] = $channel;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $name
     *
     * @return self
     */
    public function removeChannel($name)
    {
        $this->channelOrFail($name);
        unset($this->channels[$name]);
        return $this;
    }

    /**
     * Create a new fork to configure the created job.
     *
     * @param array $attributes
     *
     * @return static
     */
    protected function fork(array $attributes)
    {
        return new QueueProxy($this, $attributes, $this->proxyPusher);
    }

    /**
     * @param string $name
     *
     * @return Driver
     */
    protected function channelOrFail($name)
    {
        if (isset($this->channels[$name])) {
            return $this->channels[$name];
        }
        throw new ResourceNotFoundException("Channel $name is not known.");
    }

    /**
     * @param array        $attributes
     * @param string|array $operation
     * @param array        $arguments
     *
     * @return Job
     */
    protected function createJob(array $attributes, $operation, array $arguments)
    {
        $attributes['channelName'] = isset($attributes['channelName']) ? $attributes['channelName'] : static::$defaultChannel;

        return (new Job($attributes))
            ->setOperation($operation)
            ->setArguments($arguments);
    }

    /**
     * Push a job onto an channel.
     *
     * @param Job $job
     *
     * @return Job
     */
    protected function pushToChannel(Job $job)
    {
        $channelName = $job->channelName() ? $job->channelName() : static::$defaultChannel;

        $state = $this->channelOrFail($channelName)->push($job, $channelName);

        $job->setState($state);

        return $job;
    }
}