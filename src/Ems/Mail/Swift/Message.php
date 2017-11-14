<?php

namespace Ems\Mail\Swift;

use Ems\Contracts\Mail\Message as MessageContract;
use Ems\Mail\MessageTrait;
use Swift_Message;
use Swift_Image;
use Swift_Attachment;
use Swift_Mime_Headers_MailboxHeader;

class Message implements MessageContract
{
    use MessageTrait;

    protected $recipientHeaderNames = ['to', 'cc', 'bcc'];

    /**
     * The Swift Message instance.
     *
     * @var \Swift_Message
     */
    protected $swiftMessage;

    /**
     * @param \Swift_Message $swiftMessage
     **/
    public function __construct(Swift_Message $swiftMessage)
    {
        $this->swiftMessage = $swiftMessage;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $address
     * @param string $name
     *
     * @return $this
     */
    public function from($address, $name = null)
    {
        $this->swiftMessage->setFrom($address, $name);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $address
     * @param string $name
     *
     * @return $this
     */
    public function sender($address, $name = null)
    {
        $this->swiftMessage->setSender($address, $name);

        return $this;
    }

    /**
     * Set the "return path" of the message.
     *
     * @param string $address
     *
     * @return $this
     */
    public function returnPath($address)
    {
        $this->swiftMessage->setReturnPath($address);

        return $this;
    }

    /**
     * Add a recipient to the message.
     *
     * @param string|array $address
     * @param string       $name
     *
     * @return $this
     */
    public function to($address, $name = null)
    {
        return $this->addAddresses($address, $name, 'To');
    }

    /**
     * Add a carbon copy to the message.
     *
     * @param string $address
     * @param string $name
     *
     * @return $this
     */
    public function cc($address, $name = null)
    {
        return $this->addAddresses($address, $name, 'Cc');
    }

    /**
     * Add a blind carbon copy to the message.
     *
     * @param string $address
     * @param string $name
     *
     * @return $this
     */
    public function bcc($address, $name = null)
    {
        return $this->addAddresses($address, $name, 'Bcc');
    }

    /**
     * Add a reply to address to the message.
     *
     * @param string $address
     * @param string $name
     *
     * @return $this
     */
    public function replyTo($address, $name = null)
    {
        return $this->addAddresses($address, $name, 'ReplyTo');
    }

    /**
     * Set the subject of the message.
     *
     * @param string $subject
     *
     * @return $this
     */
    public function subject($subject)
    {
        $this->swiftMessage->setSubject($subject);

        return $this;
    }

    /**
     * Set the html body of the message.
     *
     * @param string $html
     *
     * @return self
     **/
    public function html($html)
    {
        $this->swiftMessage->setBody($html, 'text/html');

        return $this;
    }

    /**
     * Set the plain text part of the mail.
     *
     * @param string $text
     *
     * @return self
     **/
    public function plainText($text)
    {
        $this->swiftMessage->addPart($text, 'text/plain');

        return $this;
    }

    /**
     * Set the message priority level.
     *
     * @param int $level
     *
     * @return $this
     */
    public function priority($level)
    {
        $this->swiftMessage->setPriority($level);

        return $this;
    }

    /**
     * Attach a file to the message.
     *
     * @param string $file
     * @param array  $options
     *
     * @return $this
     */
    public function attach($file, array $options = [])
    {
        $attachment = $this->createAttachmentFromPath($file);

        return $this->prepAttachment($attachment, $options);
    }

    /**
     * Attach in-memory data as an attachment.
     *
     * @param string $data
     * @param string $name
     * @param array  $options
     *
     * @return $this
     */
    public function attachData($data, $name, array $options = [])
    {
        $attachment = $this->createAttachmentFromData($data, $name);

        return $this->prepAttachment($attachment, $options);
    }

    /**
     * Embed a file in the message and get the CID.
     *
     * @param string $file
     *
     * @return string
     */
    public function embed($file)
    {
        return $this->swiftMessage->embed($this->createAttachmentFromPath($file));
    }

    /**
     * Embed in-memory data in the message and get the CID.
     *
     * @param string $data
     * @param string $name
     * @param string $contentType
     *
     * @return string
     */
    public function embedData($data, $name, $contentType = null)
    {
        $image = Swift_Image::newInstance($data, $name, $contentType);

        return $this->swiftMessage->embed($image);
    }

    /**
     * Return the subject.
     *
     * @return string
     **/
    public function getSubject()
    {
        return $this->swiftMessage->getSubject();
    }

    /**
     * Return the (html) body.
     *
     * @return string
     **/
    public function getBody()
    {
        return $this->swiftMessage->getBody();
    }

    /**
     * Remove all to, cc and bcc headers to that the target can be overwritten.
     *
     * @return bool
     **/
    public function clearRecipientHeaders()
    {
        $headers = $this->swiftMessage->getHeaders();

        foreach ($headers->getAll() as $header) {
            if (!$header instanceof Swift_Mime_Headers_MailboxHeader) {
                continue;
            }
            if (in_array(strtolower($header->getFieldName()), $this->recipientHeaderNames)) {
                $headers->removeAll($header->getFieldName());
            }
        }

        return true;
    }

    /**
     * @return \Swift_Message
     **/
    public function _swiftMessage()
    {
        return $this->swiftMessage;
    }

    /**
     * Add a recipient to the message.
     *
     * @param string|array $address
     * @param string       $name
     * @param string       $type
     *
     * @return $this
     */
    protected function addAddresses($address, $name, $type)
    {
        if (is_array($address)) {
            $this->swiftMessage->{"set{$type}"}($address, $name);

            return $this;
        }

        $this->swiftMessage->{"add{$type}"}($address, $name);

        return $this;
    }

    /**
     * Create a Swift Attachment instance.
     *
     * @param string $file
     *
     * @return \Swift_Attachment
     */
    protected function createAttachmentFromPath($file)
    {
        return Swift_Attachment::fromPath($file);
    }

    /**
     * Create a Swift Attachment instance from data.
     *
     * @param string $data
     * @param string $name
     *
     * @return \Swift_Attachment
     */
    protected function createAttachmentFromData($data, $name)
    {
        return Swift_Attachment::newInstance($data, $name);
    }

    /**
     * Prepare and attach the given attachment.
     *
     * @param \Swift_Attachment $attachment
     * @param array             $options
     *
     * @return $this
     */
    protected function prepAttachment($attachment, $options = [])
    {
        // First we will check for a MIME type on the message, which instructs the
        // mail client on what type of attachment the file is so that it may be
        // downloaded correctly by the user. The MIME option is not required.
        if (isset($options['mime'])) {
            $attachment->setContentType($options['mime']);
        }

        // If an alternative name was given as an option, we will set that on this
        // attachment so that it will be downloaded with the desired names from
        // the developer, otherwise the default file names will get assigned.
        if (isset($options['as'])) {
            $attachment->setFilename($options['as']);
        }

        $this->swiftMessage->attach($attachment);

        return $this;
    }
};
