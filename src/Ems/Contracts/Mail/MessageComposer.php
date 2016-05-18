<?php 

namespace Ems\Contracts\Mail;

use DateTime;

/**
 * The MessageComposer composes messages for a mailer and fills automatic some
 * data you want to configure outside the mailer call
 *
 * A simple example would be a 
 **/
interface MessageComposer
{

    /**
     * Build a config for $resourceId at $plannedSendDate and merge the passed
     * $data into the config data
     *
     * @param \Ems\Contracts\Mail\Message $message
     * @param \Ems\Contracts\Mail\MailConfig $config
     * @param mixed $recipient
     * @param array $data (optional)
     * @param \DateTime $plannedSendDate (optional)
     * @return void
     **/
    public function fill(Message $message, MailConfig $config, $recipient, array $data=[], DateTime $plannedSendDate=null);

    /**
     * Determine if configured data will overwrite passed data. (A configugred
     * key will overwrite the passed key in the array)
     *
     * @param bool $prefer (default:true)
     * @return self
     **/
    public function preferConfiguredData($prefer=true);

    /**
     * Assign an additional callable which will process the data before passing
     * it to the view
     *
     * Signature is: function($resourceId, array &$data){}
     *
     * @param callable $processor
     * @return self
     **/
    public function processDataWith(callable $processor);

}