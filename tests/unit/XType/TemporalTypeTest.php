<?php

namespace Ems\XType;


use Ems\XType\AbstractTypeTest;

require_once __DIR__.'/AbstractTypeTest.php';


class TemporalTypeTest extends AbstractTypeTest
{
    protected function newType()
    {
        return new TemporalType();
    }
}
