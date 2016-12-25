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

    public function __construct()
    {
        $this->useOptions = (version_compare(PHP_VERSION, '7.0.0') >= 0);
    }

    /**
     * Serializer artbitary data into a string. Throw an exception if
     * you cant serialize the data. (gettype(x) == 'resource', objects, ..)
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
        return serialize($value);
    }

    /**
     * Deserializer artbitary data from a string. Throw an exception
     * of you cant deserialize the data.
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

        if ($error = $this->unserializeError()) {
            throw new UnsupportedParameterException('Malformed serialized data: '.$error);
        }

        return $value;
    }

    protected function unserializeError()
    {
        if (!$error = error_get_last()) {
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
