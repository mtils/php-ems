<?php

namespace Ems\Contracts\Mail;

use Countable;

/**
 * Represents a result after sending a message or multiple messages
 *
 */
interface SendResult extends Countable
{
    /**
     * Returns all failed recipients
     *
     * @return array
     **/
    public function failures();

    /**
     * Returns the transport object which sends the message
     *
     * @return \Ems\Contracts\Mail\Transport
     **/
    public function transport();

}