<?php

namespace Ems\XType;


use Ems\XType\AbstractTypeTest;

require_once __DIR__.'/AbstractTypeTest.php';


class ObjectTypeTest extends ArrayAccessTest
{
    protected function newType()
    {
        return new ObjectType();
    }
}
