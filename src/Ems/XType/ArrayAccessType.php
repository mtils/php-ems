<?php

namespace Ems\XType;

class ArrayAccessType extends KeyValueType
{
    /**
     * {@inheritdoc}
     *
     * @return string
     **/
    public function group()
    {
        return self::COMPLEX;
    }
}
