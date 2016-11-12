<?php

namespace Ems\Mail;

use Ems\Contracts\Core\TextParser;
use Ems\Contracts\Mail\MessageComposer as Composer;

/**
 * This is a helper object to process the message contents (body and subject)
 * Assign it via Ems\Mail\MessageComposer::processDataWith($messageTextParser).
 **/
class MessageTextParser
{
    /**
     * @var \Ems\Contracts\Core\TextParser
     **/
    protected $parser;

    /**
     * @param \Ems\Contracts\Core\TextParser $parser
     **/
    public function __construct(TextParser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * Make it callable...changes the body and subject in $viewData.
     *
     * @param \Ems\Contracts\Mail\MailConfig $config
     * @param array                          $viewData
     **/
    public function __invoke($config, &$viewData)
    {
        if (isset($viewData[Composer::SUBJECT])) {
            $viewData[Composer::SUBJECT] = $this->parser->parse(
                $viewData[Composer::SUBJECT],
                $viewData
            );
        }

        if (isset($viewData[Composer::BODY])) {
            $viewData[Composer::BODY] = $this->parser->parse(
                $viewData[Composer::BODY],
                $viewData
            );
        }
    }
}
