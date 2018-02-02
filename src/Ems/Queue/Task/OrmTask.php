<?php
/**
 *  * Created by mtils on 23.01.18 at 09:33.
 **/

namespace Ems\Queue\Task;

use Ems\Contracts\Core\Progress;
use Ems\Contracts\Queue\Task;
use Ems\Model\OrmObject;
use Ems\Contracts\Queue\Job;

class OrmTask extends OrmObject implements Task
{
    /**
     * @var Job
     */
    protected $job;

    /**
     * {@inheritdoc}
     *
     * @return string
     **/
    public function getName()
    {
        return $this->__get('name');
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getAssociatedClass()
    {
        return $this->__get('entity_class');
    }

    /**
     * {@inheritdoc}
     *
     * @return string|int
     */
    public function getAssociatedId()
    {
        return $this->__get('entity_id');
    }

    /**
     * {@inheritdoc}
     *
     * @see self::RUNNING ...
     *
     * @return string
     */
    public function getState()
    {
        if ($this->job) {
            return $this->job->state();
        }
        return $this->__get('state');
    }

    /**
     * {@inheritdoc}
     *
     * @return Job
     */
    public function getJob()
    {
        return $this->job;
    }

    /**
     * @param Job $job
     *
     * @return $this
     */
    public function setJob(Job $job)
    {
        $this->job = $job;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return int|string
     */
    public function getCreatorId()
    {
        return $this->__get('user');
    }

    /**
     * {@inheritdoc}
     *
     * @return Progress
     */
    public function getProgress()
    {
        $progress = new Progress();
        $progress->percent = $this->__get('progress_percent');
        $progress->step = $this->__get('progress_step');
        $progress->stepName = $this->__get('progress_name');
        $progress->totalSteps = $this->__get('progress_steps');
        $progress->leftOverSeconds = $this->__get('progress_remaining');
        return $progress;
    }

    /**
     * Get the last message (if it was emitted).
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->__get('message');
    }

}