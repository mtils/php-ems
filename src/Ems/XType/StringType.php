<?php

namespace Ems\XType;

class StringType extends TypeWithLength
{
    /**
     * @var string
     **/
    public $default = '';

    /**
     * @var string
     **/
    public $charset = 'UTF-8';

    /**
     * @var string
     **/
    public $mimeType = 'text/plain';

    /**
     * {@inheritdoc}
     *
     * @return string
     **/
    public function group()
    {
        return self::STRING;
    }
}
