<?php
/**
 *  * Created by mtils on 27.01.18 at 19:08.
 **/

namespace Ems\Contracts\Queue;


use DateTime;
use Ems\Contracts\Core\Progress;
use Ems\Contracts\Core\Provider;
use Ems\Contracts\Model\SupportsTransactions;

interface TaskRepository extends Provider, SupportsTransactions
{

    /**
     * Create a new task instance with the desired attributes.
     *
     * @param array $attributes
     *
     * @return Task
     */
    public function newInstance(array $attributes);

    /**
     * Create a new task entry
     *
     * @param array|Task $task
     *
     * @return Task
     */
    public function create($task);

    /**
     * Write some attributes into the task with task id $taskId.
     *
     * @param string|int $taskId
     * @param array      $attributes
     *
     * @return bool
     */
    public function write($taskId, array $attributes);

    /**
     * Update the progress of task with id $taskId.
     *
     * @param string|int $taskId
     * @param Progress   $progress
     *
     * @return bool
     */
    public function writeProgress($taskId, Progress $progress);

    /**
     * Write a message for task with id $taskId.
     *
     * @param string|int $taskId
     * @param string     $message
     * @param string     $level (default: 'info')
     *
     * @return bool
     */
    public function writeMessage($taskId, $message, $level='info');

    /**
     * Delete task information about task with id $taskId.
     *
     * @param string|int $taskId
     *
     * @return bool
     */
    public function delete($taskId);

    /**
     * Delete all expired tasks. Just decide yourself what is meant with
     * expired. (finished, failed, ...)
     *
     * @param DateTime $newest (optional)
     *
     * @return int
     */
    public function purge(DateTime $newest=null);

    /**
     * Sync the task states with the passed jobs.
     *
     * @param Job[] $jobs
     *
     * @return int
     */
    public function sync($jobs);
}