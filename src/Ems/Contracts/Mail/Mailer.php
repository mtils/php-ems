<?php 

namespace Ems\Contracts\Mail;

/**
 * This mailer has almost the same signature as the laravel mailer. The main
 * difference is that you dont have to pass a closure to send mails. This makes
 * the code much more readable. However you can pass a callable if you like but
 * mostly the only thing you call is ->to and ->subject.
 *
 * The mailer should be injected into controllers and not replace the original
 * laravel mailer
 **/
interface Mailer
{

    /**
     * Sets the recipient or the recipients for the message
     * 
     * @example Mailer::to('foo@somewhere.com')->send('template',$data)
     * @param mixed $recipient string|array for more than one
     * @return self
     **/
    public function to($recipient);

    /**
     * Sends a message with plan text. $data has to contain the subject
     *
     * @param string $view The template name
     * @param array $data The view vars
     * @param callable $callback (optional) A closure to modify the mail before send
     **/
    public function plain($view, array $data, $callback=null);

    /**
     * Sends a html mail. $data has to contain the subject
     *
     * @param string $view The template name
     * @param array $data The view vars
     * @param callable $callback (optional) A closure to modify the mail before send
     **/
    public function send($view, array $data, $callback=null);

    /**
     * Do some processing with the passed $data in plain() and send(). Only one
     * callable will be used. This callable will be called everytime a message
     * is built.
     *
     * @param callable $processor
     * @return self
     **/
    public function processDataWith(callable $processor);

    /**
     * Do something with the view name. Change it, replace it...
     *
     * @param callable $processor
     * @return self
     **/
    public function processViewNameWith(callable $processor);

}