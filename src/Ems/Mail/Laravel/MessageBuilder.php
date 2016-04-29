<?php


namespace Ems\Mail\Laravel;

use Illuminate\Mail\Message as LaravelMessage;
use OutOfBoundsException;

/**
 * This class sits between creating the mail and sending it. It creates a
 * closure, which you normally have to pass to the laravel mailer
 *
 * A hidden feature of this class is that you can globally overwrite the to
 * address to send yourself all messages while developing
 **/
class MessageBuilder
{

    /**
     * @var array
     **/
    protected $recipients = [];

    /**
     * @var array
     **/
    protected $data;

    /**
     * @var callable
     **/
    protected $callback;

    /**
     * @var string
     **/
    protected $overwriteTo = '';

    /**
     * @param array $recipients
     * @param array $data
     * @param callable $callback (optional)
     **/
    public function __construct(array $recipients, array $data,
                                callable $callback=null)
    {

        $this->recipients = $recipients;
        $this->data = $data;
        $this->callback = $callback;

    }

    /**
     * This method will be called by the returned closure
     *
     * @param \Illuminate\Mail\Message $message
     * @return void
     **/
    public function setupMessage(LaravelMessage $message){

        $this->addRecipients($message);

        $this->setSubject($message);

        $this->callCustomBuilder($message);

    }

    /**
     * This returns the closure which will be passed to laravels mail method
     *
     * @return \Closure
     **/
    public function builder(){
        return function($message){ $this->setupMessage($message); };
    }

    /**
     * Return the mail address which overwrites all setted
     *
     * @return string
     **/
    public function getOverwriteTo()
    {
        return $this->overwriteTo;
    }

    /**
     * Set an email address which overwrites all to-addresses. This is usefull
     * for development
     *
     * @param string $email
     * @return self
     **/
    public function setOverwriteTo($email)
    {
        $this->overwriteTo = $email;
        return $this;
    }

    /**
     * Adds the recipients to the message
     *
     * @param \Illuminate\Mail\Message $message
     * @return void
     **/
    protected function addRecipients(LaravelMessage $message){

        if($overwriteTo = $this->getOverwriteTo()){
            $message->to($overwriteTo);
            return;
        }

        $first = true;

        foreach($this->recipients as $recipient){

            if($first){
                $message->to($recipient);
            }
            else{
                $message->bcc($recipient);
            }

            $first = false;

        }

    }

    /**
     * Sets tzhe subject of the message
     *
     * @param \Illuminate\Mail\Message $message
     * @return void
     **/
    protected function setSubject(LaravelMessage $message){

        if(!isset($this->data['subject'])){
            throw new OutOfBoundsException("You have to pass a subject key and value in your view data");
        }

        $message->subject($this->data['subject']);

    }

    protected function callCustomBuilder(LaravelMessage $message){

        $callback = $this->callback;

        $emsMessage = new Message($message);

        if(is_callable($callback)){
            call_user_func($callback, $emsMessage);
        }

    }

}