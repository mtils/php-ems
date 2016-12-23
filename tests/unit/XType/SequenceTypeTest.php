<?php

namespace Ems\XType;


use Ems\XType\AbstractTypeTest;

require_once __DIR__.'/AbstractTypeTest.php';


class SequenceTypeTest extends AbstractTypeTest
{
    protected function newType()
    {
        return new SequenceType();
    }
}
