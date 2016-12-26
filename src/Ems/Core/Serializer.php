<?php

namespace Ems\Core;


use Ems\Contracts\Core\Serializer as SerializerContract;
use Ems\Core\Exceptions\UnsupportedParameterException;

class Serializer implements SerializerContract
{
    /**
     * @var bool
     **/
    protected $useOptions = false;

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

        if ($value === false) {
            throw new UnsupportedParameterException('You cant serialize false with that serializer');
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
            return $value;
        }

        // This does not work on hhvm
        if ($error = $this->unserializeError()) {
            throw new UnsupportedParameterException('Malformed serialized data: '.$error);
        }

        throw new UnsupportedParameterException('Unable to deserialize data');
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
