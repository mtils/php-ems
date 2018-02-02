<?php
/**
 *  * Created by mtils on 28.01.18 at 06:42.
 **/

namespace Ems\Queue\Task;

use function call_user_func;
use DateTime;
use Ems\Contracts\Core\Progress;
use Ems\Contracts\Model\OrmObject;
use Ems\Contracts\Queue\Job;
use Ems\Contracts\Queue\Queue;
use Ems\Contracts\Queue\Task;
use Ems\Contracts\Queue\TaskRepository;
use Ems\Core\Exceptions\ResourceNotFoundException;
use Exception;

class NullTaskRepository implements TaskRepository
{

    /**
     * @var int
     */
    private static $nextId = 1;

    /**
     * @var array
     */
    protected $tasks = [];

    /**
     * @var bool
     */
    protected $inTransaction = false;

    /**
     * {@inheritdoc}
     *
     * @param mixed $id
     * @param mixed $default (optional)
     *
     * @return Task
     **/
    public function get($id, $default = null)
    {
        if (isset($this->tasks[$id])) {
            return $this->tasks[$id];
        }

        return $default;
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $id
     *
     * @throws \Ems\Contracts\Core\Errors\NotFound
     *
     * @return Task
     **/
    public function getOrFail($id)
    {
        if ($task = $this->get($id)) {
            return $task;
        }

        throw new ResourceNotFoundException("Task #$id not found.");
    }

    /**
     * {@inheritdoc}
     *
     * @param array $attributes
     *
     * @return Task
     */
    public function newInstance(array $attributes)
    {
        return new OrmTask($attributes, false);
    }


    /**
     * {@inheritdoc}
     *
     * @param array|Task $task
     *
     * @return Task
     */
    public function create($task)
    {
        $attributes = $task instanceof OrmTask ? $task->toArray() : $task;
        $attributes['id'] = static::nextId();
        $task = new OrmTask($attributes, true);
        $this->tasks[$attributes['id']] = $task;
        return $task;
    }

    /**
     * {@inheritdoc}
     *
     * @param string|int $taskId
     * @param array $attributes
     *
     * @return bool
     */
    public function write($taskId, array $attributes)
    {
        if (!isset($this->tasks[$taskId])) {
            return false;
        }

        /** @var OrmObject $task */
        $task = $this->tasks[$taskId];
        $written = false;

        foreach ($attributes as $key=>$value) {
            $task->__set($key, $value);
            $written = true;
        }

        return $written;
    }

    /**
     * Update the progress of task with id $taskId.
     *
     * @param string|int $taskId
     * @param Progress $progress
     *
     * @return bool
     */
    public function writeProgress($taskId, Progress $progress)
    {
        return $this->write($taskId, [
            'progress_percent'      => $progress->percent,
            'progress_step'         => $progress->step,
            'progress_name'         => $progress->stepName,
            'progress_steps'        => $progress->totalSteps,
            'progress_remaining'    => $progress->leftOverSeconds
        ]);
    }

    /**
     * Write a message for task with id $taskId.
     *
     * @param string|int $taskId
     * @param string $message
     * @param string $level (default: 'info')
     *
     * @return bool
     */
    public function writeMessage($taskId, $message, $level = 'info')
    {
        return $this->write($taskId, ['message' => $message]);
    }

    /**
     * Delete task information about task with id $taskId.
     *
     * @param string|int $taskId
     *
     * @return bool
     */
    public function delete($taskId)
    {
        if (!isset($this->tasks[$taskId])) {
            return false;
        }
        unset($this->tasks[$taskId]);
        return true;
    }

    /**
     * Delete all expired tasks. Just decide yourself what is meant with
     * expired. (finished, failed, ...)
     *
     * @param DateTime $newest (optional)
     *
     * @return int
     */
    public function purge(DateTime $newest = null)
    {

        $deleteIds = [];

        /**
         * @var int $taskId
         * @var Task $task
         */
        foreach ($this->tasks as $taskId=>$task) {
            if ($task->getState() == Queue::FINISHED) {
                $deleteIds[] = $taskId;
            }
        }

        foreach ($deleteIds as $taskId) {
            unset($this->tasks[$taskId]);
        }

        return count($deleteIds);
    }

    /**
     * Sync the task states with the passed jobs.
     *
     * @param Job[] $jobs
     *
     * @return int
     */
    public function sync($jobs)
    {
        $updates = 0;

        foreach ($jobs as $job) {

            if (!$task = $job->arguments()[0]) {
                continue;
            }

            $task->state = $job->state();
            $updates++;
        }

        return $updates;
    }

    /**
     * @return int
     */
    protected static function nextId()
    {
        $id = self::$nextId;
        self::$nextId++;
        return $id;
    }

    /**
     * Run the callable in a transaction.
     * begin(); $run(); commit();
     *
     * @param callable $run
     * @param int $attempts (default:1)
     *
     * @return mixed The result of the callable
     *
     * @throws Exception
     **/
    public function transaction(callable $run, $attempts = 1)
    {
        $this->inTransaction = true;
        $lastInsertedId = static::$nextId;

        try {
            $result = $run($this);
            $this->inTransaction = false;
            return $result;

        } catch (\Exception $e) {

            $this->inTransaction = false;

            if(static::$nextId == $lastInsertedId) {
                throw $e;
            }

            if (isset($this->tasks[static::$nextId])) {
                unset($this->tasks[static::$nextId]);
            }

            static::$nextId--;

            throw $e;
        }
    }

    /**
     * Return if a transaction is currently running.
     *
     * @return bool
     **/
    public function isInTransaction()
    {
        return $this->inTransaction;
    }


}