<?php
/**
 *  * Created by mtils on 04.02.18 at 06:25.
 **/

namespace Ems\Queue\Illuminate;


use Ems\Contracts\Queue\Driver;
use Ems\Contracts\Queue\Job;
use Ems\Contracts\Queue\Queue;
use Ems\Core\Lambda;
use Ems\Queue\ArgumentSerializer;
use Ems\Queue\Queue as QueueObject;
use Illuminate\Contracts\Queue\Factory as IlluminateQueueFactory;
use Illuminate\Contracts\Queue\Queue as IlluminateQueue;

class QueueDriver implements Driver
{

    /**
     * @var IlluminateQueueFactory
     */
    protected $queueFactory;

    /**
     * @var ArgumentSerializer
     */
    protected $serializer;

    /**
     * @var null|string
     */
    protected $connection;

    /**
     * QueueDriver constructor.
     *
     * @param IlluminateQueueFactory $queueFactory
     * @param ArgumentSerializer     $serializer
     * @param string|null            $connection (optional)
     */
    public function __construct(IlluminateQueueFactory $queueFactory, ArgumentSerializer $serializer, $connection=null)
    {
        $this->queueFactory = $queueFactory;
        $this->serializer = $serializer;
        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     *
     * @param Job $job
     * @param string $queueName (optional)
     *
     * @return string (The Job state)
     *
     * @throws \ReflectionException
     */
    public function push(Job $job, $queueName = '')
    {
        $operation = is_string($job->operation()) ? $job->operation() : (new Lambda($job->operation()))->toString();
        $arguments = $job->arguments();

        $queue = $this->queue();
        $data['operation'] = $operation;
        $data['arguments'] = $this->serializer->encode($arguments);

        if (!$queueName || $queueName == QueueObject::$defaultChannel) {
            $queueName = null;
        }

        $queue->push(ProxyJob::class, $data,  $queueName);

        return Queue::QUEUED;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $queueName (optional)
     *
     * @return int
     */
    public function count($queueName = '')
    {
        return $this->queue()->size($queueName);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $queueName (optional)
     *
     * @return Job|null
     */
    public function pop($queueName = '')
    {
        if(!$illuminateJob = $this->queue()->pop($queueName)) {
            return null;
        }

        return new Job([

        ]);

    }

    /**
     * {@inheritdoc}
     *
     * @param string $queueName
     *
     * @return Job[]
     */
    public function all($queueName = '')
    {
        // TODO: Implement all() method.
    }

    /**
     * @return IlluminateQueue
     */
    protected function queue()
    {
        return $this->queueFactory->connection($this->connection);
    }

}