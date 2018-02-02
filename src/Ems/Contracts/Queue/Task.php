<?php
/**
 *  * Created by mtils on 24.01.18 at 10:11.
 **/

namespace Ems\Contracts\Queue;

use Ems\Contracts\Core\Named;
use Ems\Contracts\Core\Progress;

interface Task extends Named
{
    /**
     * Return the class of an associated item if it was passed.
     *
     * @return string
     */
    public function getAssociatedClass();

    /**
     * Return the if of an associated item if it was passed.
     *
     * @return string|int
     */
    public function getAssociatedId();

    /**
     * Return the job state.
     *
     * @see self::RUNNING ...
     *
     * @return string
     */
    public function getState();

    /**
     * Get the queue job associated with this task.
     *
     * @return Job
     */
    public function getJob();

    /**
     * Get the queue job associated with this task.
     *
     * @param Job $job
     *
     * @return self
     */
    public function setJob(Job $job);

    /**
     * Get the id of the "user" who started the task.
     *
     * @return int|string
     */
    public function getCreatorId();

    /**
     * Get the progress (if is was emitted).
     *
     * @return Progress
     */
    public function getProgress();

    /**
     * Get the last message (if it was emitted).
     *
     * @return string
     */
    public function getMessage();
}