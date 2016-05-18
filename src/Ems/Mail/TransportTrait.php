<?php


namespace Ems\Mail;

use Ems\Contracts\Mail\Message;


trait TransportTrait
{

    /**
     * @var callable
     **/
    protected $_sendingListener;

    /**
     * @var callable
     **/
    protected $_sentListener;

    /**
     * Assign a listener to get informed before a message get send
     *
     * @param callable $listener
     * @return self
     **/
    public function beforeSending(callable $listener)
    {
        $this->_sendingListener = $listener;
        return $this;
    }

    /**
     * Assign a listener to get informed after a message was sent
     *
     * @param callable $listener
     * @return self
     **/
    public function afterSent(callable $listener)
    {
        $this->_sentListener = $listener;
        return $this;
    }

    /**
     * @param \Ems\Contracts\Mail\Message $message
     **/
    protected function callSendingListener(Message $message)
    {
        call_user_func($this->_sendingListener, $message);
    }

    /**
     * @param \Ems\Contracts\Mail\Message $message
     **/
    protected function callSentListener(Message $message)
    {
        call_user_func($this->_sentListener, $message);
    }

}