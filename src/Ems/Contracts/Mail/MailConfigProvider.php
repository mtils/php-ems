<?php 

namespace Ems\Contracts\Mail;

use DateTime;

/**
 * The MailConfigBuilder provides a MailConfig object 
 *
 * A simple example would be a 
 **/
interface MailConfigProvider
{

    /**
     * Return a config for $resourceName at $plannedSendDate
     *
     * @param string $resourceName
     * @param \DateTime $plannedSendDate (optional)
     * @return \Ems\Contracts\Mail\MailConfig
     **/
    public function configFor($resourceName, DateTime $plannedSendDate=null);

}