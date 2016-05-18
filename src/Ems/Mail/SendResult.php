<?php

namespace Ems\Mail;

use Ems\Contracts\Mail\SendResult as ResultContract;
use Ems\Contracts\Mail\Transport;

class SendResult implements ResultContract
{

    protected $failedRecipients = [];

    protected $recipients = [];

    protected $sendCounter = 0;

    protected $transport;

    public function __construct(Transport $transport=null)
    {
        $this->transport = $transport;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     **/
    public function failures()
    {
        return $this->failedRecipients;
    }

    /**
     * Returns the transport object which sends the message
     *
     * @return \Ems\Contracts\Mail\Transport
     **/
    public function transport()
    {
        return $this->transport;
    }

    /**
     * Returns the amount of sent messages
     *
     * @return int
     **/
    public function count()
    {
        return $this->sendCounter;
    }

    /**
     * Increment the send counter
     *
     * @see self::count()
     * @return self
     **/
    public function increment($steps=1)
    {
        $this->sendCounter += $steps;
        return $this;
    }

    /**
     * Add one (string) or multiple (array) failed recipients
     *
     * @param string|array $failedRecipient
     * @return $this;
     **/
    public function addFailedRecipient($failedRecipient)
    {
        foreach ((array)$failedRecipient as $recipient) {
            $this->failedRecipients[] = $recipient;
        }
        return $this;
    }

    /**
     * Sets the transport object
     *
     * @param \Ems\Contracts\Mail\Transport $transport
     * @return self
     **/
    public function setTransport(Transport $transport)
    {
        $this->transport = $transport;
        return $this;
    }

}