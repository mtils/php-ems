<?php


namespace Ems\Mail\Swift;

use Ems\Contracts\Mail\Message as MessageContract;
use Ems\Contracts\Mail\Mailer;
use Ems\Contracts\Mail\MailConfig;
use Swift_Message;
use Swift_Image;
use Swift_Attachment;

class Message implements MessageContract
{

    /**
     * The Swift Message instance.
     *
     * @var \Swift_Message
     */
    protected $swift;

    /**
     * @var \Ems\Contracts\Mail\Mailer
     **/
    protected $mailer;

    /**
     * @var \Ems\Contracts\Mail\MailConfig
     **/
    protected $configuration;

    public function __construct(Swift_Message $swift)
    {
        $this->swift = $swift;
    }

    /**
     * {@inheritdoc}
     *
     * @param  string  $address
     * @param  string  $name
     * @return $this
     */
    public function from($address, $name = null)
    {
        $this->swift->setFrom($address, $name);
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param  string  $address
     * @param  string  $name
     * @return $this
     */
    public function sender($address, $name = null)
    {
        $this->swift->setSender($address, $name);
        return $this;
    }

    /**
     * Set the "return path" of the message.
     *
     * @param  string  $address
     * @return $this
     */
    public function returnPath($address)
    {
        $this->swift->setReturnPath($address);
        return $this;
    }

    /**
     * Add a recipient to the message.
     *
     * @param  string|array  $address
     * @param  string  $name
     * @return $this
     */
    public function to($address, $name = null)
    {
        return $this->swift->addAddresses($address, $name, 'To');
    }

    /**
     * Add a carbon copy to the message.
     *
     * @param  string  $address
     * @param  string  $name
     * @return $this
     */
    public function cc($address, $name = null)
    {
        return $this->addAddresses($address, $name, 'Cc');
    }

    /**
     * Add a blind carbon copy to the message.
     *
     * @param  string  $address
     * @param  string  $name
     * @return $this
     */
    public function bcc($address, $name = null)
    {
        return $this->addAddresses($address, $name, 'Bcc');
    }

    /**
     * Add a reply to address to the message.
     *
     * @param  string  $address
     * @param  string  $name
     * @return $this
     */
    public function replyTo($address, $name = null)
    {
        return $this->addAddresses($address, $name, 'ReplyTo');
    }

    /**
     * Set the subject of the message.
     *
     * @param  string  $subject
     * @return $this
     */
    public function subject($subject)
    {
        $this->swift->setSubject($subject);
        return $this;
    }

    /**
     * Set the html body of the message
     *
     * @param string $html
     * @return self
     **/
    public function html($html)
    {
        $this->swift->setBody($html, 'text/html');
        return $this;
    }

    /**
     * Set the plain text part of the mail
     *
     * @param string $text
     * @return self
     **/
    public function plainText($text)
    {
        $this->swift->addPart($text, 'text/plain');
        return $this;
    }

    /**
     * Set the message priority level.
     *
     * @param  int  $level
     * @return $this
     */
    public function priority($level)
    {
        $this->swift->setPriority($level);
        return $this;
    }

    /**
     * Attach a file to the message.
     *
     * @param  string  $file
     * @param  array   $options
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
     * @param  string  $data
     * @param  string  $name
     * @param  array   $options
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
     * @param  string  $file
     * @return string
     */
    public function embed($file)
    {
        return $this->swift->embed($this->createAttachmentFromPath($file));
    }

    /**
     * Embed in-memory data in the message and get the CID.
     *
     * @param  string  $data
     * @param  string  $name
     * @param  string  $contentType
     * @return string
     */
    public function embedData($data, $name, $contentType = null)
    {
        $image = Swift_Image::newInstance($data, $name, $contentType);
        return $this->swift->embed($image);
    }

    /**
     * Return the mailer instance which allows a send() method on this mail
     *
     * @return \Ems\Contracts\Mail\Mailer
     **/
    public function mailer()
    {
        return $this->mailer;
    }

    /**
     * Add the mailer instance to allow send() method on this mail
     *
     * @param \Ems\Contracts\Mail\Mailer $mailer
     * @return self
     **/
    public function setMailer(Mailer $mailer)
    {
        $this->mailer = $mailer;
        return $this;
    }

    /**
     * Send the mail through the attached mailer
     *
     * @see self::mailer()
     * @return \Ems\Contracts\Mail\SendResult
     **/
    public function send()
    {
        $this->mailer->send($this);
    }

    /**
     * {@inheritdoc}
     *
     * @return \Ems\Contracts\Mail\MailConfig
     **/
    public function configuration()
    {
        return $this->configuration;
    }

    /**
     * {@inheritdoc}
     *
     * @param \Ems\Contracts\Mail\MailConfig $config
     * @return self
     **/
    public function setConfiguration(MailConfig $config)
    {
        $this->configuration = $config;
        return $this;
    }

    /**
     * Add a recipient to the message.
     *
     * @param  string|array  $address
     * @param  string  $name
     * @param  string  $type
     * @return $this
     */
    protected function addAddresses($address, $name, $type)
    {
        if (is_array($address)) {
            $this->swift->{"set{$type}"}($address, $name);
            return $this;
        }

        $this->swift->{"add{$type}"}($address, $name);

        return $this;

    }

    /**
     * Create a Swift Attachment instance.
     *
     * @param  string  $file
     * @return \Swift_Attachment
     */
    protected function createAttachmentFromPath($file)
    {
        return Swift_Attachment::fromPath($file);
    }

    /**
     * Create a Swift Attachment instance from data.
     *
     * @param  string  $data
     * @param  string  $name
     * @return \Swift_Attachment
     */
    protected function createAttachmentFromData($data, $name)
    {
        return Swift_Attachment::newInstance($data, $name);
    }

    /**
     * Prepare and attach the given attachment.
     *
     * @param  \Swift_Attachment  $attachment
     * @param  array  $options
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

        $this->swift->attach($attachment);

        return $this;
    }

};