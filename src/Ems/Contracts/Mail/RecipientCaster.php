<?php 

namespace Ems\Contracts\Mail;

/**
 * The RecipientCaster extracts the name and the email address out of a passed
 * recipient. The passed reciepient can be anything from a string to an user
 * object. This caster doesnt force your user class to implement another
 * interface.
 **/
interface RecipientCaster
{

    /**
     * Extract the email address of the passed recipient
     *
     * @param mixed $reciepient
     * @return string The email address
     **/
    public function email($recipient);

    /**
     * Extract the name of the passed recipient. If there is no, return null
     *
     * @param mixed $reciepient
     * @return string|null
     **/
    public function name($recipient);

}