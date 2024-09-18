<?php

namespace Ems\Mail;

use Exception;
use InvalidArgumentException;
use Ems\Contracts\Mail\SendResult as ResultContract;
use Ems\Contracts\Mail\Transport;

class SendResult implements ResultContract
{
    protected $failedRecipients = [];

    protected $errors = [];

    protected $recipients = [];

    protected $sendCounter = 0;

    protected $transport;

    public function __construct(Transport $transport = null)
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
     * Returns the transport object which sends the message.
     *
     * @return \Ems\Contracts\Mail\Transport
     **/
    public function transport()
    {
        return $this->transport;
    }

    /**
     * {@inheritdoc}
     *
     * @param int $failedRecipientIndex
     *
     * @return \Exception
     **/
    public function error($failedRecipientIndex)
    {
        return isset($this->errors[$failedRecipientIndex]) ? $this->errors[$failedRecipientIndex] : null;
    }

    /**
     * Returns the amount of sent messages.
     *
     * @return int
     **/
    #[\ReturnTypeWillChange]
    public function count()
    {
        return $this->sendCounter;
    }

    /**
     * Increment the send counter.
     *
     * @see self::count()
     *
     * @return self
     **/
    public function increment($steps = 1)
    {
        $this->sendCounter += $steps;

        return $this;
    }

    /**
     * Add one (string) or multiple (array) failed recipients.
     *
     * @param string|array $failedRecipient
     * @patam \Exception $error (optional)
     *
     * @return $this;
     **/
    public function addFailedRecipient($failedRecipient, Exception $e = null)
    {
        $failedRecipients = (array) $failedRecipient;

        if (count($failedRecipients) > 1 && $e) {
            throw new InvalidArgumentException('If you pass an exception you can only pass one failed reciepient');
        }

        foreach ($failedRecipients as $recipient) {
            $this->failedRecipients[] = $recipient;
        }

        if ($e) {
            $this->errors[count($this->failedRecipients) - 1] = $e;
        }

        return $this;
    }

    /**
     * Sets the transport object.
     *
     * @param \Ems\Contracts\Mail\Transport $transport
     *
     * @return self
     **/
    public function setTransport(Transport $transport)
    {
        $this->transport = $transport;

        return $this;
    }
}
