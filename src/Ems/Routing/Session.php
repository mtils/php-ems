<?php
/**
 *  * Created by mtils on 15.01.2022 at 17:52.
 **/

namespace Ems\Routing;

use Ems\Contracts\Core\Serializer as SerializerContract;
use Ems\Contracts\Routing\Session as SessionContract;
use Ems\Contracts\Core\Storage;
use Ems\Core\Serializer;
use LogicException;
use SessionHandler;
use SessionHandlerInterface;
use UnexpectedValueException;

use function array_key_exists;
use function call_user_func;
use function session_create_id;

class Session implements SessionContract
{
    /**
     * @var array|null
     */
    protected $data;

    /**
     * @var string
     */
    protected $id = '';

    /**
     * @var SessionHandlerInterface
     */
    protected $handler;

    /**
     * @var SerializerContract
     */
    protected $serializer;

    /**
     * @var callable
     */
    protected $idGenerator;

    public function __construct(SessionHandlerInterface $handler=null, SerializerContract $serializer=null)
    {
        $this->handler = $handler ?: new SessionHandler();
        $this->serializer = $serializer ?: new Serializer();
        $this->idGenerator = function () {
            return session_create_id();
        };
    }

    public function isStarted(): bool
    {
        return $this->data !== null;
    }

    public function start(): bool
    {
        if ($this->isStarted()) {
            throw new LogicException('The session was already started');
        }
        $this->startIfNotStarted();
        return true;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        if (!$this->id) {
            $this->id = call_user_func($this->idGenerator, $this);
        }
        return $this->id;
    }

    /**
     * @param string $id
     * @return SessionContract
     */
    public function setId(string $id): SessionContract
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return array
     */
    public function toArray() : array
    {
        $this->startIfNotStarted();
        return $this->data;
    }

    public function offsetExists($offset) : bool
    {
        $this->startIfNotStarted();
        return array_key_exists($offset, $this->data);
    }

    public function offsetGet($offset)
    {
        $this->startIfNotStarted();
        return $this->data[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->startIfNotStarted();
        $this->data[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        $this->startIfNotStarted();
        $this->remove($offset);
    }

    /**
     * @param array|null $keys
     * @return $this
     */
    public function clear(array $keys = null) : Session
    {
        if ($keys === []) {
            return $this;
        }

        $this->startIfNotStarted();

        if ($keys === null) {
            $this->data = [];
            return $this;
        }

        foreach ($keys as $key) {
            $this->remove($key);
        }
        return $this;
    }

    /**
     * @return bool
     */
    public function persist() : bool
    {
        if (!$this->data) {
            return $this->handler->destroy($this->getId());
        }
        return $this->handler->write(
            $this->getId(),
            $this->serializer->serialize($this->data)
        );
    }

    /**
     * @return bool
     */
    public function isBuffered() : bool
    {
        return true;
    }

    /**
     * @return string
     */
    public function storageType() : string
    {
        return Storage::UTILITY;
    }

    public function setIdGenerator(callable $generator) : Session
    {
        $this->idGenerator = $generator;
        return $this;
    }

    protected function remove($key)
    {
        if (isset($this->data[$key])) {
            unset($this->data[$key]);
        }
    }

    protected function startIfNotStarted()
    {
        if (!$this->isStarted()) {
            $this->data = $this->getDataFromHandler();
        }
    }

    /**
     * @return array
     */
    protected function getDataFromHandler() : array
    {
        if (!$raw = $this->handler->read($this->getId())) {
            return [];
        }
        $data = $this->serializer->deserialize($raw);
        if (!is_array($data)) {
            throw new UnexpectedValueException("Unserialized data is not array");
        }
        return $data;
    }
}