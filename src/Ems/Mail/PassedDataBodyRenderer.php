<?php

namespace Ems\Mail;

use UnderflowException;
use Ems\Contracts\Mail\BodyRenderer;


/**
 * This is a bogus renderer which just returns the passed body
 **/
class PassedDataBodyRenderer implements BodyRenderer
{
    /**
     * {@inheritdoc}
     *
     * @param \Ems\Contracts\Mail\MailConfig
     * @param aray $data
     * @return string
     **/
    public function html(MailConfig $config, array $data)
    {
        if (isset($data[MessageComposer::BODY])) {
            return $data[MessageComposer::BODY];
        }

        throw new UnderflowException('No mail body was passed');
    }


    /**
     * {@inheritdoc}
     *
     * @param \Ems\Contracts\Mail\MailConfig
     * @param aray $data
     * @return string
     **/
    public function plainText(MailConfig $config, array $data)
    {
        return '';
    }
}
