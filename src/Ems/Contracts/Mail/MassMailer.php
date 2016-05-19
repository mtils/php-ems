<?php 

namespace Ems\Contracts\Mail;

use DateTime;

interface MassMailer extends Mailer
{

    public function send(MailConfig $config, DateTime $plannedSendDate=null);

}