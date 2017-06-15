<?php

namespace Ems\XType;


use Ems\XType\AbstractTypeTest;

require_once __DIR__.'/PairTypeTest.php';


class RangeTypeTest extends PairTypeTest
{
    protected function newType()
    {
        return new RangeType();
    }
}
