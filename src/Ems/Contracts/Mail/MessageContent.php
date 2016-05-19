<?php 

namespace Ems\Contracts\Mail;

use Ems\Contracts\Core\Identifiable;

interface MessageContent extends Identifiable
{

    /**
     * Returns the subject of this message
     *
     * @return string
     **/
    public function subject();

    /**
     * Returns the body of this message
     *
     * @return string
     **/
    public function body();

    /**
     * Returns the configuration which will build the message
     *
     * @return \Ems\Contracts\Mail\MailConfig
     **/
    public function config();

    /**
     * Return the person which wrote the content
     *
     * @return mixed
     **/
    public function originator();

}