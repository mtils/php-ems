<?php

namespace Ems\XType;

use Ems\Contracts\XType\HasMinMax;

class StringType extends AbstractType implements HasMinMax
{
    use MinMaxProperties;

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
