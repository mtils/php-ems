<?php
/**
 *  * Created by mtils on 23.01.18 at 14:14.
 **/

namespace Ems\Queue;

use function call_user_func;
use Ems\Contracts\Queue\Driver;
use Ems\Contracts\Queue\Job;
use Ems\Contracts\Queue\Queue as QueueContract;

class QueueProxy extends Queue
{
    /**
     * @var QueueContract
     */
    protected $root;

    public function __construct(QueueContract $root, array $attributes, callable $pusher)
    {
        $this->root = $root;
        $this->attributes = $attributes;
        $this->proxyPusher = $pusher;
    }

    /**
     * {@inheritdoc}
     *
     * @return string[]
     */
    public function channelNames()
    {
        return $this->root->channelNames();
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
        return $this->root->count($channel);
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
        return $this->root->pop($channel);
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
        return $this->root->all($channel);
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
        $this->root->addChannel($name, $channel);
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
        $this->root->removeChannel($name);
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param array $attributes
     *
     * @return static
     */
    protected function fork(array $attributes)
    {
        return new static($this->root, $attributes, $this->proxyPusher);
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
        return call_user_func($this->proxyPusher, $job);
    }
}