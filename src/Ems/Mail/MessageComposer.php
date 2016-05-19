<?php


namespace Ems\Mail;

use UnderflowException;
use Ems\Contracts\Mail\MessageComposer as ComposerContract;
use Ems\Contracts\Mail\BodyRenderer;
use Ems\Contracts\Mail\MailConfig;
use Ems\Contracts\Mail\Message;
use Ems\Contracts\Mail\AddressExtractor;
use Ems\Contracts\Mail\MessageContentProvider;

class MessageComposer implements ComposerContract
{

    protected $extractor;

    protected $bodyRenderer;

    protected $contentProvider;

    protected $preferConfiguredData = false;

    protected $dataProcessor;

    public function __construct(AddressExtractor $extractor=null, BodyRenderer $renderer=null,
                                MessageContentProvider $contentProvider=null)
    {
        $this->extractor = $extractor ?: new GuessingAddressExtractor;
        $this->bodyRenderer = $bodyRenderer ?: new PassedDataBodyRenderer;
        $this->contentProvider = $contentProvider;
        $this->dataProcessor = function($config, &$data){};
    }

    /**
     * Fill the message with contents for $plannedSendDate depending on $recipient
     * on $recipient and $data.
     *
      * @param \Ems\Contracts\Mail\MailConfig $config
     * @param \Ems\Contracts\Mail\Message $message
     * @param array $data (optional)
     * @param \DateTime $plannedSendDate (optional)
     * @return void
     **/
    public function fill(MailConfig $config, Message $message, array $data=[], DateTime $plannedSendDate=null)
    {

        $viewData = $this->mergePassedWithConfigData($config->data(), $data);

        $recipient = $message->recipient();

        $viewData[self::RECIPIENT] = $recipient;

        $this->assureContentsInData($config, $viewData, $plannedSendDate);

        call_user_func($this->dataProcessor, $config, $viewData);

        $toEmail = $this->extractor->email($recipient);

        $message->to($toEmail);
        $message->sender($this->extractor->email($this->getSender($config, $message)));

        if ($html = $this->bodyRenderer->html($config, $viewData)) {
            $message->html($html);
        }

        if ($text = $this->bodyRenderer->plainText($config, $viewData)) {
            $message->plainText($text);
        }

        if (isset($viewData[self::ORIGINATOR])) {
            $message->from($this->extractor->email($viewData[self::ORIGINATOR]));
        }
    }

    /**
     * Determine if configured data will overwrite passed data. (A configured
     * key will overwrite the passed key in the array)
     *
     * @param bool $prefer (default:true)
     * @return self
     **/
    public function preferConfiguredData($prefer=true)
    {
        $this->preferConfiguredData = $prefer;
        return $this;
    }

    /**
     * Assign an additional callable which will process the data before passing
     * it to the view
     *
     * Signature is: function($resourceId, array &$data){}
     *
     * @param callable $processor
     * @return self
     **/
    public function processDataWith(callable $processor)
    {
        $this->dataProcessor = $processor;
        return $this;
    }

    protected function assureContentsInData(MailConfig $config, array &$data, DateTime $plannedSendDate=null)
    {

        if ($this->contentProvider) {
            return $this->assureContentsByProvider($config, $data, $plannedSendDate);
        }

        if (!isset($data[self::SUBJECT])){
            throw new UnderflowException('You have to pass subject (and body) inside $data');
        }

    }

    protected function assureFromContentProvider(MailConfig $config, array &$data, DateTime $plannedSendDate=null)
    {

        if ( isset($data[self::SUBJECT]) && isset($data[self::BODY]) && !$this->preferConfiguredData ) {
            return;
        }

        $content = $this->contentProvider->contentFor($config, $plannedSendDate);

        if ( isset($data[self::SUBJECT]) && !$content ) {
            return;
        }

        if ( !$config && !isset($data[self::SUBJECT]) ) {
            throw new UnderflowException('No subject passed and no contents found by provider');
        }

        if ( $originator = $content->originator() ) {
            $data[self::ORIGINATOR] = $originator;
        }

        if (!isset($data[self::SUBJECT]) || $this->preferConfiguredData) {
            $data[self::SUBJECT] = $content->subject();
        }

        if (!isset($data[self::BODY]) || $this->preferConfiguredData) {
            $data[self::BODY] = $content->body();
        }

    }

    protected function getSender(MailConfig $config, Message $message)
    {
        if (!$message->originator() || $this->preferConfiguredData) {
            return $config->sender();
        }
        return $message->originator();
    }

    protected function mergePassedWithConfigData(array $configData, array $passedData)
    {
        if ($this->preferConfiguredData) {
            return array_merge($passedData, $configData);
        }
        return array_merge($configData, $passedData);
    }
}