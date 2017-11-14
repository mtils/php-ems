<?php

namespace Ems\Mail;

use UnderflowException;
use Ems\Contracts\Mail\BodyRenderer;
use Ems\Contracts\Mail\MailConfig as MailConfigContract;

/**
 * This is a bogus renderer which just returns the passed body.
 **/
class PassedDataBodyRenderer implements BodyRenderer
{
    /**
     * {@inheritdoc}
     *
     * @param MailConfigContract $config
     * @param array              $data
     *
     * @return string
     **/
    public function html(MailConfigContract $config, array $data)
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
     *
     * @return string
     **/
    public function plainText(MailConfigContract $config, array $data)
    {
        return '';
    }
}
