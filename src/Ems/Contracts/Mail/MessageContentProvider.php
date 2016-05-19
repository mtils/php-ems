<?php


namespace Ems\Contracts\Mail;

use DateTime;


interface MessageContentProvider
{
    public function contentsFor(MailConfig $config, DateTime $plannedSendDate=null);
}