<?php

namespace Ems\Core\Serializer;

use Ems\Contracts\Core\Serializer;

class JsonSerializer implements Serializer
{

    /**
     * @var int
     **/
    const NUMERIC_CHECK = JSON_NUMERIC_CHECK;

    /**
     * @var int
     **/
    const PRETTY = JSON_PRETTY_PRINT;

    /**
     * @var int
     **/
    const UNESCAPED_SLASHES = JSON_UNESCAPED_SLASHES;

    /**
     * @var int
     **/
     const UNESCAPED_UNICODE = JSON_UNESCAPED_UNICODE;

     /**
     * @var int
     **/
     const PRESERVE_ZERO_FRACTION = JSON_PRESERVE_ZERO_FRACTION;

     /**
     * @var int
     **/
     const BIGINT_AS_STRING = JSON_BIGINT_AS_STRING;

    /**
     * @var string
     **/
     const DEPTH = 'depth';

     /**
     * @var string
     **/
     const AS_ARRAY = 'array';

    /**
     * @var bool
     */
     protected $defaultAsArray = false;

    /**
     * @var bool
     */
     protected $defaultPrettyPrint = false;

    /**
     * {@inheritdoc}
     *
     * @return string
     **/
    public function mimeType()
    {
        return 'application/json';
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $value
     * @param array $options (optional)
     *
     * @return string
     **/
    public function serialize($value, array $options=[])
    {

        $serialized = json_encode(
            $value,
            $this->bitmask($options),
            $this->getDepth($options)
        );

        return $serialized;

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
        $deserialized = json_decode(
            $string,
            $this->shouldDecodeAsArray($options),
            $this->getDepth($options),
            $this->bitmask($options, true)
        );

        return $deserialized;

    }

    /**
     * Configure if it should return array by default in deserialize.
     *
     * @param bool $asArray
     *
     * @return $this
     */
    public function asArrayByDefault($asArray=true)
    {
        $this->defaultAsArray = $asArray;
        return $this;
    }

    /**
     * Configure if it should return array by default in deserialize.
     *
     * @param bool $pretty
     *
     * @return $this
     */
    public function prettyByDefault($pretty=true)
    {
        $this->defaultPrettyPrint = $pretty;
        return $this;
    }

    /**
     * Build a bitmask out of $options to use them with json_*
     *
     * @param array $options
     * @param bool  $forDeserialize (default: false)
     *
     * @return int
     **/
    protected function bitmask(array $options, $forDeserialize=false)
    {
        $bitmask = 0;

        $prettyPrintWasSet = false;

        foreach ($options as $key=>$value) {

            if (in_array($key, [static::DEPTH, static::AS_ARRAY])) {
                continue;
            }

            if ($key == static::PRETTY) {
                $prettyPrintWasSet = true;
            }
            if ($value) {
                $bitmask = $bitmask | $key;
            }

        }

        if (!$forDeserialize && !$prettyPrintWasSet && $this->defaultPrettyPrint) {
            $bitmask  = $bitmask | static::PRETTY;
        }

        return $bitmask;

    }

    /**
     * @param array $options
     *
     * @return int
     **/
    protected function getDepth(array $options)
    {
        if (isset($options[static::DEPTH])) {
            return $options[static::DEPTH];
        }
        return 512;
    }

    /**
     * @param array $options
     *
     * @return bool
     **/
    protected function shouldDecodeAsArray(array $options)
    {
        if (isset($options[static::AS_ARRAY])) {
            return $options[static::AS_ARRAY];
        }
        return $this->defaultAsArray;
    }

}
