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
     * Sets the recipient or the recipients for the message. If more than one
     * Recipients are passed, the mail will be generated multiple times for
     * each recipient.
     *
     * Send one mail to one recipient:
     * @example Mailer::to('foo@somewhere.com')->send('template',$data)
     *
     * Send two separatly generated mails to two recipients
     * @example Mailer::to('foo@bar.de', 'bar@foo.de')->send(...)
     *
     * Send a mass mail to many recpients
     * @example $users = Users::all(); Mailer::to($users)->send(...)
     *
     * @param mixed $recipient string|array for more than one
     * @return self
     **/
    public function to($recipient);

    /**
     * Create a message and set all parts manually.
     *
     * @example Mailer::message()
     *                ->to('foo@bar.de')
     *                ->subject('Hello')
     *                ->body('bye')
     *                ->send()
     *
     * @example Mailer::message('foo@bar.de', 'Hello', 'bye')->send()
     *
     * @param string $to The recipient, email or something handled by ReciepientCaster
     * @param string $subject
     * @param string $body The text body
     * @return \Ems\Contracts\Mail\Message
     **/
    public function message($to='', $subject='', $body='');

    /**
     * Sends one or more mails (Depends on count($to)).
     *
     * @param string $resourceId A resource id like registrations.activate
     * @param array $data (optional) The view vars (subject, body, ...)
     * @param callable $callback (optional) A closure to modify the mail(s) before send
     * @return \Ems\Contracts\Mail\SendResult
     **/
    public function send($resourceId, array $data=[], $callback=null);

    /**
     * Send a message manually. This method is also called by messages created
     * by self::message(). You can send only one mail at a time with this method
     *
     * @param \Ems\Contracts\Mail $message
     * @return \Ems\Contracts\Mail\SendResult
     **/
    public function sendMessage(Message $message);

    /**
     * Assign a listener which will be informed when a message will be sent.
     * Signature is: function(\Ems\Contracts\Mail\Message $message){}
     *
     * @param callable $listener
     * @return self
     **/
    public function beforeSending(callable $listener);

    /**
     * Assign a listener which will be informed when a message was sent.
     * Signature is: function(\Ems\Contracts\Mail\Message $message){}
     *
     * @param callable $listener
     * @return self
     **/
    public function afterSent(callable $listener);

}