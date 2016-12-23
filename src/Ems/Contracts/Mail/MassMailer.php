<?php

namespace Ems\Contracts\Mail;

use DateTime;

/**
 * The MassMailer sends many mails to the recipients stored in a MailConfig.
 **/
interface MassMailer extends Mailer
{
    /**
     * Send all mails of $config for $plannedSendDate. If $plannedSendDate is
     * omitted take the next one in sequence.
     *
     * @param \Ems\Contracts\Mail\MailConfig $config
     * @param \DateTime                      $plannedSendDate (optional)
     *
     * @return \Ems\Contracts\Mail\SendResult
     **/
    public function sendAll(MailConfig $config, DateTime $plannedSendDate = null);
}
