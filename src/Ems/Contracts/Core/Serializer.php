<?php

namespace Ems\Contracts\Core;

/**
 * Use this object to serialize and deserialize data. The serializer dont
 * have to handle all kind of data. JSON can also a serializer and cant
 * deserialize all objects.
 **/
interface Serializer
{

    /**
     * Return a mimetype for the serialized data
     *
     * @return string
     **/
    public function mimeType();

    /**
     * Serializer artbitary data into a string. Throw an exception if
     * you cant serialize the data. (gettype(x) == 'resource', objects, ..)
     *
     * @param mixed $value
     * @param array $options (optional)
     *
     * @return string
     **/
    public function serialize($value, array $options=[]);

    /**
     * Deserializer artbitary data from a string. Throw an exception
     * of you cant deserialize the data.
     *
     * @param string $string
     * @param array $options (optional)
     *
     * @return mixed
     **/
    public function deserialize($string, array $options=[]);
}
