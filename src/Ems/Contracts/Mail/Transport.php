<?php 

namespace Ems\Contracts\Mail;

interface Transport
{

    public function newMessage();


    public function send(Message $message);


    public function beforeSending(callable $listener);

    public function afterSent(callable $listener);

}