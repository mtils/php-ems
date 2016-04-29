<?php 

namespace Ems\Contracts\Mail;

/**
 * A Message is the actual message object which will be sent to a recipient.
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


}