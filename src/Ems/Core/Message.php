<?php
/**
 *  * Created by mtils on 24.08.19 at 07:37.
 **/

namespace Ems\Core;

use Ems\Contracts\Core\Message as AbstractMessage;
use Ems\Core\Exceptions\KeyNotFoundException;

use function get_class;
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
 * @property string type The type of message in/out/log/custom
 * @property bool   accepted
 * @property bool   ignored
 * @property string transport The transport media to send the message
 * @property array custom The manually set attributes
 * @property array envelope The metadata of this message like http headers
 * @property mixed payload The raw payload
 */
class Message extends AbstractMessage
{

    public function __set(string $key, $value)
    {
        switch ($key) {
            case 'type':
                $this->type = $value;
                return;
            case 'accepted':
                $this->setAccepted($value);
                return;
            case 'ignored':
                $this->setAccepted(is_bool($value) ? !$value : $value);
                return;
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
                return;
        }
        throw new KeyNotFoundException("Key $key not found in " . get_class($this));
    }

    /**
     * Fluently set values.
     *
     * @param string|array $key
     * @param mixed $value
     * @return $this
     */
    public function set($key, $value=null) : AbstractMessage
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
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        $this->custom[$offset] = $value;
    }

    /**
     * @param string|int $offset
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        unset($this->custom[$offset]);
    }

}