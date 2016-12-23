<?php

namespace Ems\Mail\Laravel;

use Ems\Contracts\Mail\MessageContentProvider;
use Ems\Contracts\Mail\MailConfig;
use Illuminate\Database\Eloquent\Model;
use DateTime;

class EloquentMessageContentProvider implements MessageContentProvider
{
    public $configForeignKey = 'mail_configuration_id';

    /**
     * @var \Illuminate\Database\Eloquent\Model
     **/
    protected $model;

    /**
     * @param \Illuminate\Database\Eloquent\Model $model
     **/
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * {@inheritdoc}
     *
     * @param \Ems\Contracts\Mail\MailConfig $config
     * @param \DateTime                      $plannedSendDate (optional)
     *
     * @return \Ems\Contracts\Mail\MailContent
     **/
    public function contentsFor(MailConfig $config, DateTime $plannedSendDate = null)
    {
        return $this->model->where($this->configForeignKey, $config->getId())->first();
    }
}
