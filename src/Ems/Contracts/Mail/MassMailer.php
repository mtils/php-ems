<?php 

namespace Ems\Contracts\Mail;

interface MassMailer extends Mailer
{

    public function send(Configuration $config);

}