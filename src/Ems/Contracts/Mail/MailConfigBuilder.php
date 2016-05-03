<?php 

namespace Ems\Contracts\Mail;

use DateTime;

/**
 * The MailConfigBuilder provides a MailConfig object 
 *
 * A simple example would be a 
 **/
interface MailConfigBuilder
{

    /**
     * Build a config for $resourceId at $plannedSendDate and merge the passed
     * $data into the config data
     *
     * @param string $resourceId
     * @param array $data (optional)
     * @param \DateTime $plannedSendDate (optional)
     * @return \Ems\Contracts\Mail\MailConfig
     **/
    public function buildFor($resourceId, array $data=[], DateTime $plannedSendDate=null);

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