<?php 

namespace Ems\Contracts\Mail;

use Ems\Contracts\Core\Named;

/**
 * This interface represents a Recipient (user/contact). The named interface
 * is used to build a header like this: $getName <$recipientAddress>
 **/
interface Recipient extends Named
{

    /**
     * Return the address (generated) mails should be sent to
     *
     * @return string
     **/
    public function recipientAddress();

}