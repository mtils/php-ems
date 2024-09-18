<?php
/**
 *  * Created by mtils on 24.08.19 at 07:37.
 **/

namespace Ems\Contracts\Core;

use ArrayAccess;
use ArrayIterator;
use Countable;
use Ems\Core\Collections\ItemNotFoundException;
use Ems\Core\Exceptions\KeyNotFoundException;
use Ems\Core\Exceptions\UnsupportedUsageException;
use Iterator;
use IteratorAggregate;
use UnexpectedValueException;

use function array_key_exists;
use function get_class;
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
 * @property-read string type The type Message::TYPE_INPUT|Message::TYPE_OUTPUT|Message::TYPE_LOG
 * @property-read bool accepted
 * @property-read bool ignored
 * @property-read string transport The transport media to send the message
 * @property-read array custom The manually set attributes
 * @property-read array envelope The metadata of this message like http headers
 * @property-read mixed payload The raw payload
 */
abstract class Message implements ArrayAccess, IteratorAggregate, Countable
{
    /**
     * Type input
     */
    const TYPE_INPUT = 'input';

    /**
     * Type output
     */
    const TYPE_OUTPUT = 'output';

    /**
     * Type log
     */
    const TYPE_LOG = 'log';

    /**
     * Type custom
     */
    const TYPE_CUSTOM = 'custom';

    /**
     * Sent through network
     */
    const TRANSPORT_NETWORK = 'network';

    /**
     * Sent through terminal (tty)
     */
    const TRANSPORT_TERMINAL = 'terminal';

    /**
     * Sent through Inter Process Communication
     */
    const TRANSPORT_IPC = 'pic';

    /**
     * Sent internally in application
     */
    const TRANSPORT_APP = 'app';

    /**
     * The custom attribute "pool". All manually added attributes.
     */
    const POOL_CUSTOM = 'custom';

    const POOL_GET = 'get';

    const POOL_POST = 'post';

    const POOL_COOKIE = 'cookie';

    const POOL_FILES = 'files';

    const POOL_SERVER = 'server';

    const POOL_ARGV = 'argv';

    const POOL_ENV = 'env';

    const POOL_ROUTE = 'route';

    /**
     * @var string
     */
    protected $type = self::TYPE_CUSTOM;

    /**
     * @var bool|null
     */
    protected $accepted;

    /**
     * @var string
     */
    protected $transport = self::TRANSPORT_APP;

    /**
     * @var array
     */
    protected $custom = [];

    /**
     * @var array
     */
    protected $envelope = [];

    /**
     * @var mixed
     */
    protected $payload;

    /**
     * @param array|mixed $payload Pass an array to set custom attributes, everything else
     * @param array $envelope
     * @param string $type (optional)
     */
    public function __construct($payload=[], array $envelope=[], string $type=self::TYPE_CUSTOM)
    {
        $this->payload = $payload;
        if (is_array($payload)) {
            $this->custom = $payload;
        }
        $this->envelope = $envelope;
        $this->type = $type;
    }

    /**
     * Get a value from (parsed) attributes.
     *
     * @param string $id
     * @param mixed $default
     * @return mixed|null
     */
    public function get($id, $default = null)
    {
        if (array_key_exists($id, $this->custom)) {
            return $this->custom[$id];
        }
        return $default;
    }

    /**
     * @param string $id
     * @return mixed|null
     *
     * @throws ItemNotFoundException
     */
    public function getOrFail($id)
    {
        $value = $this->get($id, new None());
        if ($value instanceof None) {
            throw new ItemNotFoundException("Attribute $id no found");
        }
        return $value;
    }

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
                return $this->toArray();
            case 'envelope':
                return $this->envelope;
            case 'payload':
                return $this->payload;
        }
        throw new KeyNotFoundException("Key $key not found in " . get_class($this));
    }

    /**
     * Return true if the input was accepted. If nobody accepted it this returns
     * false even it was not ignored.
     *
     * @return bool
     */
    public function isAccepted() : bool
    {
        return is_bool($this->accepted) && $this->accepted;
    }

    /**
     * Return true if the input was ignored. If nobody ignored it this returns
     * false even it was not accepted.
     *
     * @return bool|null
     */
    public function isIgnored()
    {
        return is_bool($this->accepted) && !$this->accepted;
    }

    /**
     * Mark the message as accepted.
     *
     * @return self
     */
    public function accept() : Message
    {
        $this->accepted = true;
        return $this;
    }

    /**
     * Mark the message as ignored.
     *
     * @return self
     */
    public function ignore() : Message
    {
        $this->accepted = false;
        return $this;
    }

    /**
     * @param string|int $offset
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($offset) : bool
    {
        return isset($this->custom[$offset]);
    }

    /**
     * @param string|int $offset
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->custom[$offset];
    }

    /**
     * @param string|int $offset
     * @param mixed $value
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        throw new UnsupportedUsageException('Setting values is not supported by AbstractMessage');
    }

    /**
     * @param string|int $offset
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        throw new UnsupportedUsageException('Unsetting values is not supported by AbstractMessage');
    }

    /**
     * @return array
     */
    public function toArray() : array
    {
        return $this->custom;
    }

    /**
     * @return ArrayIterator
     */
    #[\ReturnTypeWillChange]
    public function getIterator() : Iterator
    {
        return new ArrayIterator($this->toArray());
    }

    /**
     * @return int
     */
    #[\ReturnTypeWillChange]
    public function count()
    {
        return count($this->toArray());
    }

    /**
     * Check and save accepted property.
     *
     * @param mixed $value
     * @return void
     */
    protected function setAccepted($value)
    {
        if (!is_null($value) && !is_bool($value)) {
            throw new UnexpectedValueException('accepted can only be boolean or null');
        }
        $this->accepted = $value;
    }
}