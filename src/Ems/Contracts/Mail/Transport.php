<?php

namespace Ems\Contracts\Mail;

/**
 * This transport interface does not really represent a transport
 * like a Swift transport. The interface is to small for that. The
 * Mailer Contracts are sort of highlevel API. The complete Laravel
 * Mailer could be a transport.
 **/
interface Transport
{
    /**
     * Creates a new message.
     *
     * @return \Ems\Contracts\Mail\Message
     **/
    public function newMessage();

    /**
     * Sends a message.
     *
     * @param \Ems\Contracts\Mail\Message $message
     *
     * @return \Ems\Contracts\Mail\SendResult
     **/
    public function send(Message $message);

    /**
     * Assign a listener to get informed before a message get send.
     *
     * @param callable $listener
     *
     * @return self
     **/
    public function beforeSending(callable $listener);

    /**
     * Assign a listener to get informed after a message was sent.
     *
     * @param callable $listener
     *
     * @return self
     **/
    public function afterSent(callable $listener);
}
