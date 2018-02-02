<?php
/**
 *  * Created by mtils on 22.01.18 at 14:11.
 **/

namespace Ems\Contracts\Queue;

use Ems\Contracts\Core\HasMethodHooks;

/**
 * Interface Tasker
 *
 * The tasker is a high level interface to work with processing queues.
 * You can have a process list and see running jobs with a tasker.
 *
 * Tasker must have at least an onBefore(run) and onAfter(run) hook.
 *
 * @package Ems\Contracts\Queue
 */
interface Tasker extends ConfiguresJob, HasMethodHooks
{
    /**
     * Push a new job to the queue. You don't have to build a job, just
     * push a callable or something like a callable into it.
     *
     * @example self::run([MyClass::class, 'process'])
     * @example self::run('MyClass::process')
     *
     * @param string|callable $run
     * @param array           $arguments
     *
     * @return Task
     **/
    public function run($run, array $arguments=[]);

    /**
     * Mark a job to be associated with $class (and optionally $id). This is
     * handy when you create a database object and need to do something in the
     * queue for it and have some association with it.
     *
     * @param string     $class
     * @param string|int $id (optional)
     *
     * @return self
     */
    public function forEntity($class, $id=null);

    /**
     * Mark a started job as been started by user with id $userId.
     *
     * @param string|int $user
     *
     * @return self
     */
    public function byUser($user);

    /**
     * Give the task a name.
     *
     * @param string $name
     *
     * @return self
     */
    public function named($name);

    /**
     * Return the assigned queue to directly push jobs.
     *
     * @return Queue
     */
    public function getQueue();
}