<?php

namespace Ems\XType;

class BoolType extends AbstractType
{
    /**
     * @var bool
     **/
    public $default = false;

    /**
     * {@inheritdoc}
     *
     * @return string
     **/
    public function group()
    {
        return self::BOOL;
    }
}
