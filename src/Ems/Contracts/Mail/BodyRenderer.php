<?php

namespace Ems\Contracts\Mail;

/**
 * The BodyRenderer renders the email body. You can use Blade or Smarty (urgs) or
 * whatever to render behind this interface.
 **/
interface BodyRenderer
{
    /**
     * Return the html body for the email.
     *
     * @param MailConfig $config
     * @param array      $data
     *
     * @return string
     **/
    public function html(MailConfig $config, array $data);

    /**
     * Return the plain text body for the email.
     *
     * @param MailConfig $config
     * @param array      $data
     *
     * @return string
     **/
    public function plainText(MailConfig $config, array $data);
}
