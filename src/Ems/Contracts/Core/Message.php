<?php
/**
 *  * Created by mtils on 24.08.19 at 07:37.
 **/

namespace Ems\Contracts\Core;

use ArrayAccess;

use Ems\Core\Collections\ItemNotFoundException;

use function array_key_exists;
use function is_array;
use function is_bool;

/**
 * Class Message
 *
 * A message is a value object to send from an emitter to a listener
 * (handler/receiver). This is the base for input (requests), responses...
 *
 * The class holds the payload data in payload. This could be a string or whatever
 * raw (binary) data.
 * The ArrayAccess interface and toArray() gives access to "payload data". If
 * you receive json data the json formatted string would be in payload, the
 * data will be accessible via ArrayAccess/toArray().
 *
 * Regarding events: In general better connect signatures instead of writing
 * event classes.
 *
 * @package Ems\Contracts\Core
 *
 * @property string transport The transport media to send the message
 * @property array custom The manually set attributes
 * @property array envelope The metadata of this message like http headers
 * @property mixed payload The raw payload
 */
class Message extends AbstractMessage
{

    /**
     * @param string $key
     * @return mixed
     */
    public function __get(string $key)
    {
        switch ($key) {
            case 'type':
                return $this->type;
            case 'accepted':
                return $this->isAccepted();
            case 'ignored':
                return $this->isIgnored();
            case 'transport':
                return $this->transport;
            case 'custom':
                return $this->custom;
            case 'envelope':
                return $this->envelope;
            case 'payload':
                return $this->payload;
        }
        return null;
    }

    public function __set(string $key, $value)
    {
        switch ($key) {
            case 'transport':
                $this->transport = $value;
                return;
            case 'custom':
                $this->custom = $value;
                return;
            case 'envelope':
                $this->envelope = $value;
                return;
            case 'payload':
                $this->payload = $value;
        }
    }

    /**
     * Fluently set values.
     *
     * @param string|array $key
     * @param mixed $value
     * @return $this
     */
    public function set($key, $value=null) : Message
    {
        $values = is_array($key) ? $key : [$key=>$value];
        foreach ($values as $key=>$value) {
            $this->offsetSet($key, $value);
        }
        return $this;
    }

    /**
     * @param string|int $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->custom[$offset] = $value;
    }

    /**
     * @param string|int $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->custom[$offset]);
    }

}