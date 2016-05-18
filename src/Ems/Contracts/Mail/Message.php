<?php 

namespace Ems\Contracts\Mail;

/**
 * A Message is the actual message object which will be sent to a recipient.
 * Better said it is a proxy to the native message (like Swift_Message)
 */
interface Message
{

    /**
     * Add a "from" address to the message.
     *
     * @param  string  $address
     * @param  string  $name
     * @return $this
     */
    public function from($address, $name = null);

    /**
     * Set the "sender" of the message.
     *
     * @param  string  $address
     * @param  string  $name
     * @return $this
     */
    public function sender($address, $name = null);

    /**
     * Set the "return path" of the message.
     *
     * @param  string  $address
     * @return $this
     */
    public function returnPath($address);

    /**
     * Add a recipient to the message.
     *
     * @param  string|array  $address
     * @param  string  $name
     * @return $this
     */
    public function to($address, $name = null);

    /**
     * Add a carbon copy to the message.
     *
     * @param  string  $address
     * @param  string  $name
     * @return $this
     */
    public function cc($address, $name = null);

    /**
     * Add a blind carbon copy to the message.
     *
     * @param  string  $address
     * @param  string  $name
     * @return $this
     */
    public function bcc($address, $name = null);

    /**
     * Add a reply to address to the message.
     *
     * @param  string  $address
     * @param  string  $name
     * @return $this
     */
    public function replyTo($address, $name = null);

    /**
     * Set the subject of the message.
     *
     * @param  string  $subject
     * @return $this
     */
    public function subject($subject);

    /**
     * Set the html body of the message
     *
     * @param string $html
     * @return self
     **/
    public function html($html);

    /**
     * Set the plain text part of the mail
     *
     * @param string $text
     * @return self
     **/
    public function plainText($text);

    /**
     * Set the message priority level.
     *
     * @param  int  $level
     * @return $this
     */
    public function priority($level);

    /**
     * Attach a file to the message.
     *
     * @param  string  $file
     * @param  array   $options
     * @return $this
     */
    public function attach($file, array $options = []);

    /**
     * Attach in-memory data as an attachment.
     *
     * @param  string  $data
     * @param  string  $name
     * @param  array   $options
     * @return $this
     */
    public function attachData($data, $name, array $options = []);

    /**
     * Embed a file in the message and get the CID.
     *
     * @param  string  $file
     * @return string
     */
    public function embed($file);

    /**
     * Embed in-memory data in the message and get the CID.
     *
     * @param  string  $data
     * @param  string  $name
     * @param  string  $contentType
     * @return string
     */
    public function embedData($data, $name, $contentType = null);

    /**
     * Return the mailer instance which allows a send() method on this mail
     *
     * @return \Ems\Contracts\Mail\Mailer
     **/
    public function mailer();

    /**
     * Add the mailer instance to allow send() method on this mail
     *
     * @param \Ems\Contracts\Mail\Mailer $mailer
     * @return self
     **/
    public function setMailer(Mailer $mailer);

    /**
     * Send the mail through the attached mailer
     *
     * @see self::mailer()
     * @return \Ems\Contracts\Mail\SendResult
     **/
    public function send();

    /**
     * Return the configuration which build the message
     *
     * @return \Ems\Contracts\Mail\MailConfig
     **/
    public function configuration();

    /**
     * Set the mail configuration which builds this mail
     *
     * @param \Ems\Contracts\Mail\MailConfig $config
     * @return self
     **/
    public function setConfiguration(MailConfig $config);

}