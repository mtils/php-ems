<?php

namespace Ems\XType;


use Ems\XType\AbstractTypeTest;

require_once __DIR__.'/NumberTypeTest.php';


class UnitTypeTest extends NumberTypeTest
{
    protected function newType()
    {
        return new UnitType();
    }
}
