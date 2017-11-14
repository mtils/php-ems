<?php

namespace Ems\Mail;

use Ems\Core\NamedObject;
use Ems\Contracts\Mail\MailConfig as MailConfigContract;
use Ems\Contracts\Mail\RecipientList as ListContract;

class MailConfig extends NamedObject implements MailConfigContract
{
    /**
     * @var string
     **/
    protected $resourceName;

    /**
     * @var MailConfig
     **/
    protected $parent;

    /**
     * @var array
     **/
    protected $children = [];

    /**
     * @var \Ems\Contracts\Mail\RecipientList
     **/
    protected $recipientList;

    /**
     * @var string
     **/
    protected $template;

    /**
     * @var array
     **/
    protected $data = [];

    /**
     * @var string
     **/
    protected $sender;

    /**
     * @var string
     **/
    protected $senderName;

    /**
     * @var bool
     **/
    protected $generatedOccurences = false;

    /**
     * @var
     **/
    protected $schedule;

    /**
     * {@inheritdoc}
     *
     * @return \Ems\Contracts\Mail\MailConfig
     **/
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param \Ems\Contracts\Mail\MailConfig $config
     **/
    public function setParent(MailConfigContract $config)
    {
        $this->parent = $config;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return \Traversable|array[\Ems\Contracts\Mail\MailConfig]
     **/
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * {@inheritdoc}
     *
     * @return \Ems\Contracts\Mail\RecipientList
     **/
    public function recipientList()
    {
        return $this->recipientList;
    }

    /**
     * @param \Ems\Contracts\Mail\RecipientList $list
     *
     * @return self
     **/
    public function setRecipientList(ListContract $list)
    {
        $this->recipientList = $list;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     **/
    public function template()
    {
        return $this->template;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $template
     *
     * @return self
     **/
    public function setTemplate($template)
    {
        $this->template = $template;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     **/
    public function data()
    {
        return $this->data;
    }

    /**
     * @param array $data
     *
     * @return self
     **/
    public function setData(array $data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     **/
    public function sender()
    {
        return $this->sender;
    }

    /**
     * @param string $sender
     *
     * @return self
     **/
    public function setSender($sender)
    {
        $this->sender = $sender;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     **/
    public function senderName()
    {
        return $this->senderName;
    }

    /**
     * @param string $name
     *
     * @return self
     **/
    public function setSenderName($name)
    {
        $this->senderName = $name;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     **/
    public function areOccurrencesGenerated()
    {
        return $this->generatedOccurences;
    }

    /**
     * @param bool $enabled (optional)
     *
     * @return self
     **/
    public function enableGeneratedOccurences($enabled = true)
    {
        $this->generatedOccurences = $enabled;
        return $this;
    }

    public function schedule()
    {
    }
}
