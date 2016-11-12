<?php

namespace Ems\Mail\Swift;

use UnexpectedValueException;
use Ems\Contracts\Mail\Transport as TransportContract;
use Ems\Contracts\Mail\Message as MessageContract;
use Ems\Mail\TransportTrait;
use Ems\Mail\SendResult;
use Swift_Message;
use Swift_Mailer;
use Exception;

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
     *
     * @return \Ems\Mail\Swift\Message
     **/
    public function newMessage()
    {
        return new Message(new Swift_Message());
    }

    /**
     * {@inheritdoc}
     *
     * @param \Ems\Contracts\Mail\Message $message
     *
     * @return \Ems\Contracts\Mail\SendResult
     **/
    public function send(MessageContract $message)
    {
        $this->callSendingListener($message);

        $swiftMessage = $this->getSwiftMessage($message);

        $failedRecipients = [];

        $result = $this->newResult();

        try {
            $this->swiftMailer->send($swiftMessage, $failedRecipients);

            $result->increment();
            $result->addFailedRecipient($failedRecipients);

            $this->callSentListener($message);
        } catch (Exception $e) {
            $result->addFailedRecipient($this->guessRecipient($message), $e);
        }

        return $result;
    }

    /**
     * Get the swift message out of an message.
     *
     * @param \Ems\Contracts\Mail\Message $message
     *
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

    /**
     * Try to guess the reciepient of the message to add it to the failures.
     *
     * @param \Ems\Contracts\Mail\Message $message
     *
     * @return mixed
     **/
    protected function guessRecipient(MessageContract $message)
    {
        if ($recipient = $message->recipient()) {
            return $recipient;
        }

        $swiftMessage = $this->getSwiftMessage($message);

        if (!$to = $swiftMessage->getTo()) {
            return;
        }

        if (!count($to)) {
            return;
        }

        return $to[0];
    }
}
