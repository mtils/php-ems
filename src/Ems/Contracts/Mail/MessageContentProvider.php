<?php

namespace Ems\Contracts\Mail;

use DateTime;

interface MessageContentProvider
{
    /**
     * Get the contents for $config at $plannedSendDate.
     *
     * @param \Ems\Contracts\Mail\MailConfig $config
     * @param \DateTime                      $plannedSendDate (optional)
     *
     * @return \Ems\Contracts\Mail\MailContent
     **/
    public function contentsFor(MailConfig $config, DateTime $plannedSendDate = null);
}
