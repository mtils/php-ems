<?php

namespace Ems\Mail\Swift;

use UnexpectedValueException;
use Ems\Contracts\Mail\Transport as TransportContract;
use Ems\Contracts\Mail\Message as MessageContract;
use Ems\Mail\TransportTrait;
use Ems\Mail\SendResult;
use Swift_Message;
use Swift_Mailer;

class Transport implements TransportContract
{
    use TransportTrait;

    /**
     * @var \Swift_Mailer
     **/
    protected $swiftMailer;

    /**
     * @param \Swift_Mailer $swiftMailer
     **/
    public function __construct(Swift_Mailer $swiftMailer)
    {
        $this->swiftMailer = $swiftMailer;
    }

    /**
     * {@inheritdoc}
     * @return \Ems\Mail\Swift\Message
     **/
    public function newMessage()
    {
        return new Message(new Swift_Message);
    }

    /**
     * {@inheritdoc}
     *
     * @param \Ems\Contracts\Mail\Message $message
     * @return \Ems\Contracts\Mail\SendResult
     **/
    public function send(MessageContract $message)
    {

        $this->instanceCheck($message);
        $this->callSendingListener($message);

        $swiftMessage = $this->getSwiftMessage($message);

        $failedRecipients = [];

        $this->swiftMailer->send($swiftMessage, $failedRecipients);

        $result = $this->newResult();

        $result->increment();
        $result->addFailedRecipient($failedRecipients);

        $this->callSentListener($message);

        return $result;

    }

    /**
     * Get the swift message out of an message
     *
     * @param \Ems\Contracts\Mail\Message $message
     * @return \Swift_Message
     **/
    protected function getSwiftMessage(MessageContract $message)
    {
        $swiftMessage = $message->_swiftMessage();
        if (!$swiftMessage instanceof Swift_Message) {
            throw new UnexpectedValueException('Swift\Transport can only send messages with swift messages');
        }
        return $swiftMessage;
    }

    /**
     * @return \Ems\Contracts\Mail\SendResult
     **/
    protected function newResult()
    {
        return new SendResult($this);
    }
}