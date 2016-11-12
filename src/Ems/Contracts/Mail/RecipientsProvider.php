<?php


namespace Ems\Contracts\Mail;

interface RecipientsProvider
{
    /**
     * Returns an iterable (\Traversable) of Recipient objects.
     *
     * @param \Ems\Contracts\Mail\RecipientList $list
     *
     * @return \Traversable
     **/
    public function all(RecipientList $list);
}
