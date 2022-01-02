<?php

namespace Ems\Core;


use Ems\Contracts\Core\Extendable;
use Ems\Contracts\Core\Serializer as SerializerContract;
use Ems\Core\Exceptions\UnsupportedParameterException;
use Ems\Core\Patterns\ExtendableTrait;

use function call_user_func;

/**
 * This is "THE SERIALIZER". Its normal usage is just to replace serialize()
 * and unserialize().
 * But you can also use it as a factory for other formats. Use the extendable
 * interface for it and call self::forMimeType($mime)->deserialize().
 */
class Serializer implements SerializerContract, Extendable
{
    use ExtendableTrait;

    /**
     * @var bool
     **/
    protected $useOptions = false;

    /**
     * @var callable
     */
    protected $errorGetter;

     /**
      * @var string
      **/
     protected $serializeFalseAs = '--|false-serialized|--';

    public function __construct(callable $errorGetter=null)
    {
        $this->useOptions = (version_compare(PHP_VERSION, '7.0.0') >= 0);
        $this->errorGetter = $errorGetter ?: function () { return error_get_last(); };
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     **/
    public function mimeType()
    {
        return 'application/vnd.php.serialized';
    }

    /**
     * {@inheritdoc}
     * This serializer cant handle false values because php returns false when
     * tryin to deserializing malformed serialized data.
     *
     * @param mixed $value
     * @param array $options (optional)
     *
     * @return string
     **/
    public function serialize($value, array $options=[])
    {
        if (is_resource($value)) {
            throw new UnsupportedParameterException('You cant serialize a resource');
        }

        if ($value === $this->serializeFalseAs) {
            throw new UnsupportedParameterException('You cant serialize '.$this->serializeFalseAs.' cause its internally used to encode false');
        }

        if ($value === false) {
            $value = $this->serializeFalseAs;
        }

        return serialize($value);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $string
     * @param array $options (optional)
     *
     * @return mixed
     **/
    public function deserialize($string, array $options=[])
    {
        $value = $this->useOptions? @unserialize($string, $options) : @unserialize($string);

        if ($value !== false) {
            return $value === $this->serializeFalseAs ? false : $value;
        }

        // This does not work on hhvm
        if ($error = $this->unserializeError()) {
            throw new UnsupportedParameterException('Malformed serialized data: '.$error);
        }

        throw new UnsupportedParameterException('Unable to deserialize data');
    }

    /**
     * Return a serializer for $mimetype.
     *
     * @param string $mimetype
     * @return SerializerContract
     */
    public function forMimeType(string $mimetype) : SerializerContract
    {
        return call_user_func($this->getExtension($mimetype));
    }

    /**
     * Return the error that did occur when unserialize was called
     *
     * @return string|bool
     **/
    protected function unserializeError()
    {
        if (!$error = call_user_func($this->errorGetter)) {
            return false;
        }

        if ($error['file'] != __FILE__) {
            return false;
        }

        if (strpos($error['message'], 'unserialize(') !== 0) {
            return false;
        }

        return $error['message'];
    }
}
