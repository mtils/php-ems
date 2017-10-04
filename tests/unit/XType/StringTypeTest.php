<?php

namespace Ems\XType;


use Ems\XType\AbstractTypeTest;

require_once __DIR__.'/NumberTypeTest.php';


class StringTypeTest extends NumberTypeTest
{
    protected function newType()
    {
        return new StringType();
    }
}
