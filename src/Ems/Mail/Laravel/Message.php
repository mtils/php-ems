<?php


namespace Ems\Mail\Laravel;

use Illuminate\Mail\Message as LaravelMessage;
use Ems\Contracts\Mail\Message as MessageContract;

class Message implements MessageContract
{
    protected $laravelMessage;

    public function __construct(LaravelMessage $laravelMessage)
    {
        $this->laravelMessage = $laravelMessage;
    }

    /**
     * Add a "from" address to the message.
     *
     * @param string $address
     * @param string $name
     *
     * @return $this
     */
    public function from($address, $name = null)
    {
        return $this->laravelMessage->from($address, $name);
    }

    /**
     * Set the "sender" of the message.
     *
     * @param string $address
     * @param string $name
     *
     * @return $this
     */
    public function sender($address, $name = null)
    {
        return $this->laravelMessage->sender($address, $name);
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
        return $this->laravelMessage->returnPath($address);
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
        return $this->laravelMessage->to($address, $name);
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
        return $this->laravelMessage->cc($address, $name);
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
        return $this->laravelMessage->bcc($address, $name);
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
        return $this->laravelMessage->replyTo($address, $name);
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
        return $this->laravelMessage->subject($subject);
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
        return $this->laravelMessage->priority($level);
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
        return $this->laravelMessage->attach($file, $options);
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
        return $this->laravelMessage->attachData($data, $name, $options);
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
        return $this->laravelMessage->embed($file);
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
        return $this->laravelMessage->embedData($data, $name, $contentType);
    }
}
