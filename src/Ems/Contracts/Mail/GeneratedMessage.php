<?php 

namespace Ems\Contracts\Mail;

interface GeneratedMessage extends Message
{

    /**
     * Returns the configuration which built this message
     *
     * @return \Ems\Contracts\Mail\Configuration
     **/
    public function configuration();

}