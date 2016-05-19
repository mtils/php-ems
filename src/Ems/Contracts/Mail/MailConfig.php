<?php 

namespace Ems\Contracts\Mail;

use Ems\Contracts\Core\Named;
use Ems\Contracts\Core\AppliesToResource;

interface MailConfig extends Named, AppliesToResource
{

    const SUBJECT = 'subject';

    const BODY = 'body';

    /**
     * Return a parent MailConfig
     *
     * @return \Ems\Contracts\Mail\MailConfig
     **/
    public function parent();

    /**
     * Return an iterable of children MailConfig objects
     *
     * @return \Traversable|array [\Ems\Contracts\Mail\MailConfig]
     **\
    public function children();

    /**
     * Return the recipient list
     *
     * @return \Ems\Contracts\Mail\RecipientList
     **/
    public function recipientList();

    /**
     * Return a template name/path for the mail you want to build
     *
     * @return string
     **/
    public function template();

    /**
     * Return an array of data which will be passed to the view as view variables
     *
     * @return array
     **/
    public function data();

    /**
     * Return the sender for this email (email address)
     *
     * @return string
     **/
    public function sender();

    /**
     * Return the sender name for this email (its name)
     *
     * @return string
     **/
    public function senderName();


    /**
     * Return if mails of occurrences of the schedule will be moderated (written)
     * by a human. This detemermins if a config can have one or multiple mail
     * contents. If occurrences are generated then one mail content will be
     * applied to all mails. If not, every occurrence needs a new mail content
     *
     * @return bool
     **/
    public function areOccurrencesGenerated();

    public function schedule();

//     public function 

}