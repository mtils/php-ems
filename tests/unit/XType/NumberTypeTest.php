<?php

namespace Ems\XType;


use Ems\XType\AbstractTypeTest;

require_once __DIR__.'/AbstractTypeTest.php';


class NumberTypeTest extends AbstractTypeTest
{
    public function test_min_max_constraints()
    {
        $type = $this->newType();
        $type->min = 3;
        $type->max = 550;
        $this->assertEquals(3, $type->min);
        $this->assertEquals(3, $type->constraints->min);
        $this->assertEquals(550, $type->max);
        $this->assertEquals(550, $type->constraints->max);
    }

    protected function newType()
    {
        return new NumberType();
    }
}
