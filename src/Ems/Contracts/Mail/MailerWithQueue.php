<?php 

namespace Ems\Contracts\Mail;

/**
 * This mailer can do all Mailer does plus queing the mails
 */
interface MailerWithQueue extends Mailer
{

    /**
     * Sends a mail via queing
     *
     * @param string $resourceId A resource id like registrations.activate
     * @param array $data The view vars
     * @param callable $callback (optional) A closure to modify the mail before send
     * @param string $queue Der name of the queue
     **/
    public function queue($resourceId, array $data=[], $callback=null, $queue=null);

    /**
     * Sends the mail later
     *
     * @param int $delay Delay in seconds
     * @param string $resourceId A resource id like registrations.activate
     * @param array $data The view vars
     * @param callable $callback (optional) A closure to modify the mail before send
     * @param string $queue The name of the queue
     **/
    public function later($delay, $resourceId, array $data=[], $callback=null, $queue=null);

}