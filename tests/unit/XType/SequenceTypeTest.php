<?php

namespace Ems\XType;


require_once __DIR__.'/NumberTypeTest.php';


class SequenceTypeTest extends NumberTypeTest
{
    protected function newType()
    {
        return new SequenceType();
    }
}
