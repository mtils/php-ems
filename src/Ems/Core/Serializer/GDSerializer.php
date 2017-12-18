<?php

namespace Ems\Core\Serializer;

use Ems\Contracts\Core\Serializer;
use Ems\Core\Exceptions\DataIntegrityException;
use Ems\Core\Exceptions\UnsupportedParameterException;
use Ems\Contracts\Core\Type;

/**
 * This serializer serializes a GD resource into a string and
 * deserialized it back into a resource.
 **/
class GDSerializer implements Serializer
{

    /**
     * The quality option for jpeg files
     *
     * @var string
     **/
    const QUALITY = 'quality';

    /**
     * @var array
     **/
    protected $knownMimetypes = [
        'image/gif',
        'image/jpeg',
        'image/png'
    ];

    /**
     * @var string
     **/
    protected $mimeType = 'image/png';

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
     * Set the mimeType of this serializer.
     *
     * @param string $mimeType
     *
     * @return self
     **/
    public function setMimeType($mimeType)
    {
        if (!in_array($mimeType, $this->knownMimetypes)) {
            throw new UnsupportedParameterException("I cannot handle mimetype '$mimeType', only " . implode(',', $this->knownMimetypes ));
        }

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

        if (!$this->isGDResource($value)) {
            throw new UnsupportedParameterException('I can only serialize gd resource not ' . Type::of($value));
        }


        // Didnt get this to work...
        // $streamUri = 'php://memory';
        // $stream = fopen($streamUri, 'w');

        ob_start();

        if ($this->mimeType == 'image/gif') {
            imagegif($value, null);
        }

        if ($this->mimeType == 'image/jpeg') {
            $quality = isset($options[static::QUALITY]) ? $options[static::QUALITY] : 80;
            imagejpeg($value, null, $quality);
        }

        if ($this->mimeType == 'image/png') {
            $quality = isset($options[static::QUALITY]) ? $options[static::QUALITY] : 8;
            imagepng($value, null, $quality);
        }

        return ob_get_clean();

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

        if ($resource = @imagecreatefromstring($string)) {
            return $resource;
        }

        throw new DataIntegrityException('Passed data not readable by gd');
    }

    /**
     * Check if the passed resource is a gd resource.
     *
     * @param mixed $resource
     *
     * @return bool
     **/
    protected function isGDResource($resource)
    {
        if (!is_resource($resource)) {
            return false;
        }

        if (strtolower(get_resource_type($resource) != 'gd')) {
            return false;
        }

        return true;
    }
}
