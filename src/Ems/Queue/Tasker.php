<?php
/**
 *  * Created by mtils on 23.01.18 at 09:56.
 **/

namespace Ems\Queue;

use Ems\Contracts\Queue\Job;
use Ems\Contracts\Queue\Queue as QueueContract;
use Ems\Contracts\Queue\Task;
use Ems\Contracts\Queue\Tasker as TaskerContract;
use Ems\Contracts\Queue\TaskRepository;
use Ems\Core\Lambda;
use Ems\Core\Patterns\HookableTrait;
use function array_merge;
use function in_array;

class Tasker implements TaskerContract
{
    use JobConfiguration;
    use HookableTrait;

    /**
     * @var QueueContract
     */
    protected $queue;

    /**
     * @var TaskRepository
     */
    protected $repository;

    /**
     * @var array
     */
    protected $channels = [];

    public function __construct(QueueContract $queue, TaskRepository $repository, array $attributes=[])
    {
        $this->queue = $queue;
        $this->repository = $repository;
        $this->attributes = $attributes;
    }

    /**
     * {@inheritdoc}
     *
     * @param string|callable $run
     * @param array $arguments
     *
     * @return Task
     **/
    public function run($run, array $arguments = [])
    {

        $lambda = new Lambda($run);

        return $this->repository->transaction(function($repo) use ($lambda, $arguments) {

            $taskTemplate = $this->newTask($lambda);

            $this->callBeforeListeners('run', [$taskTemplate, $lambda, $arguments]);

            $task = $this->repository->create($taskTemplate);

            $job = $this->createJob($task, $lambda, $arguments);

            // We can just ignore to write state because typically the job
            // is typically started in another process
            $task->setJob($job);

            $this->callAfterListeners('run', [$taskTemplate, $job]);

            return $task;

        });
    }

    /**
     * {@inheritdoc}
     *
     * @param string $class
     * @param string|int $id (optional)
     *
     * @return self
     */
    public function forEntity($class, $id = null)
    {
        return $this->forkWith('entity_class', $class)
            ->forkWith('entity_id', $id);
    }

    /**
     * {@inheritdoc}
     *
     * @param string|int $user
     *
     * @return self
     */
    public function byUser($user)
    {
        return $this->forkWith('user', $user);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $name
     *
     * @return self
     */
    public function named($name)
    {
        return $this->forkWith('name', $name);
    }

    /**
     * Return the assigned queue to directly push jobs.
     *
     * @return QueueContract
     */
    public function getQueue()
    {
        return $this->queue;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     **/
    public function methodHooks()
    {
        return ['run'];
    }

    /**
     * @param array $attributes
     *
     * @return static
     */
    protected function fork(array $attributes)
    {
        $forkAttributes = array_merge($this->attributes, $attributes);
        return new static($this->queue, $this->repository, $forkAttributes);
    }

    /**
     * Create the task via the repository.
     *
     * @param Lambda $lambda
     *
     * @return Task
     */
    protected function newTask($lambda)
    {
        return $this->repository->newInstance($this->buildTaskAttributes());
    }

    /**
     * Create a job in the queue to run the task.
     *
     * @param Task   $task
     * @param Lambda $operation
     * @param array  $operationArgs
     *
     * @return Job
     */
    protected function createJob(Task $task, Lambda $operation, array $operationArgs)
    {
        $queue = $this->queue;

        if (isset($this->attributes['plannedStart']) && $this->attributes['plannedStart']) {
            $queue = $queue->at($this->attributes['plannedStart']);
        }

        if (isset($this->attributes['maxTries']) && $this->attributes['maxTries']) {
            $queue = $queue->tries($this->attributes['maxTries']);
        }

        if (isset($this->attributes['timeout']) && $this->attributes['timeout']) {
            $queue = $queue->timeout($this->attributes['timeout']);
        }

        if (isset($this->attributes['channelName']) && $this->attributes['channelName']) {
            $queue = $queue->onChannel($this->attributes['channelName']);
        }

        $jobArgs = [$task->getId(), $operation->toString(), $operationArgs];

        return $queue->run([TaskProxyJob::class, 'run'], $jobArgs);
    }

    /**
     * @return array
     */
    protected function buildTaskAttributes()
    {
        $taskAttributes = [];

        foreach ($this->attributes as $key=>$value) {
            if (!in_array($key, ['plannedStart', 'maxTries', 'timeout', 'channelName'])) {
                $taskAttributes[$key] = $value;
            }
        }

        return $taskAttributes;
    }

}