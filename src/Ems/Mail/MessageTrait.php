<?php

namespace Ems\Mail;

use Ems\Contracts\Mail\Mailer as MailerContract;
use Ems\Contracts\Mail\MailConfig as ConfigContract;

trait MessageTrait
{
    /**
     * @var \Ems\Contracts\Mail\Mailer
     **/
    protected $_mailer;

    /**
     * @var \Ems\Contracts\Mail\MailConfig
     **/
    protected $_config;

    /**
     * @var mixed
     **/
    protected $_recipient;

    /**
     * @var mixed
     **/
    protected $_originator;

    /**
     * {@inheritdoc}
     *
     * @return \Ems\Contracts\Mail\Mailer
     **/
    public function mailer()
    {
        return $this->_mailer;
    }

    /**
     * {@inheritdoc}
     *
     * @param \Ems\Contracts\Mail\Mailer $mailer
     *
     * @return self
     **/
    public function setMailer(MailerContract $mailer)
    {
        $this->_mailer = $mailer;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @see self::mailer()
     *
     * @return \Ems\Contracts\Mail\SendResult
     **/
    public function send()
    {
        return $this->_mailer->send($this);
    }

    /**
     * {@inheritdoc}
     *
     * @return \Ems\Contracts\Mail\MailConfig
     **/
    public function config()
    {
        return $this->_config;
    }

    /**
     * {@inheritdoc}
     *
     * @param \Ems\Contracts\Mail\MailConfig $config
     *
     * @return self
     **/
    public function setConfig(ConfigContract $config)
    {
        $this->_config = $config;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return mixed
     **/
    public function recipient()
    {
        return $this->_recipient;
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $recipient
     *
     * @return self
     **/
    public function setRecipient($recipient)
    {
        $this->_recipient = $recipient;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return mixed
     **/
    public function originator()
    {
        return $this->_originator;
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $originator
     *
     * @return self
     **/
    public function setOriginator($originator)
    {
        $this->_originator = $originator;

        return $this;
    }
}
