<?php

namespace Ems\XType;


use Ems\XType\AbstractTypeTest;

require_once __DIR__.'/AbstractTypeTest.php';


class PairTypeTest extends AbstractTypeTest
{
    public function test_min_and_max_is_2()
    {
        $type = $this->newType();
        $this->assertEquals(2, $type->min);
        $this->assertEquals(2, $type->max);
//         $this->assertEquals(2, $type->constraints->min);
    }

    /**
     * @expectedException InvalidArgumentException
     **/
    public function test_setting_min_throws_exception_if_value_not_2()
    {
        $type = $this->newType();
        $type->min = 13;
    }

    /**
     * @expectedException InvalidArgumentException
     **/
    public function test_setting_max_throws_exception_if_value_not_2()
    {
        $type = $this->newType();
        $type->min = 1;
    }

    public function test_setting_min_and_max_throws_no_exception_if_value_is_2()
    {
        $type = $this->newType();
        $type->min = 2;
        $type->max = 2;
    }

    /**
     * Overwritten to check the min/max access
     *
     * @expectedException InvalidArgumentException
     **/
    public function test_unset_removes_constraint()
    {
        $type = $this->newType();
        $type->min = 15;
    }

    /**
     * Overwritten to check the min/max access
     *
     * @expectedException InvalidArgumentException
     **/
    public function test_throws_exception_when_setting_max()
    {
        $type = $this->newType();
        $type->max = 15;
    }

    protected function newType()
    {
        return new PairType();
    }
}
