<?php

namespace Ems\Core\Serializer;

use Ems\Contracts\Core\Serializer;
use UnexpectedValueException;
use Ems\Contracts\Core\Type;

/**
 * The blob serializer is a fake serializer. It checks if something is
 * a string just just returns that string.
 * This is usefull do drop in in every class which attempt to serialize
 * but shouldnt.
 **/
class BlobSerializer implements Serializer
{

    /**
     * @var string
     **/
    protected $mimeType = 'application/octet-stream';

    /**
     * {@inheritdoc}
     *
     * @return string
     **/
    public function mimeType()
    {
        return $this->mimeType;
    }

    /**
     * If you do some serializing yourself just add you own mimeType to it.
     *
     * @param string $mimeType
     *
     * @return string
     **/
    public function setMimeType($mimeType)
    {
        $this->mimeType = $mimeType;
        return $this;
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

        if (!is_string($value)) {
            throw new UnexpectedValueException('I can only work with strings, not ' . Type::of($value));
        }

        return $value;

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
        if (!is_string($string)) {
            throw new UnexpectedValueException('I can only work with strings, not ' . Type::of($string));
        }

        return $string;
    }

}
