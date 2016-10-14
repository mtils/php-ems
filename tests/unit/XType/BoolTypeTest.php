<?php


namespace Ems\XType;


use Ems\XType\AbstractTypeTest;

require_once __DIR__.'/AbstractTypeTest.php';


class BoolTypeTest extends AbstractTypeTest
{

    protected function newType()
    {
        return new BoolType;
    }

}
