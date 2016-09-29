<?php


namespace Ems\Core;


class GenericTemporalUnitTest extends \Ems\TestCase
{

    public function test_implements_interface()
    {
        $this->assertInstanceOf(
            'Ems\Contracts\Core\TemporalUnit',
            $this->newUnit()
        );
    }

    public function test_year_property()
    {

        $unit = $this->newUnit('2016-05-31 12:32:14');

        $this->assertEquals(2016, $unit->year);

        $unit->year = 2014;

        $this->assertEquals(2014, $unit->year);

    }

    public function test_month_property()
    {

        $unit = $this->newUnit('2016-05-15 12:32:14');

        $this->assertEquals(5, $unit->month);

        $unit->month = 6;

        $this->assertEquals(6, $unit->month);

    }

    protected function newUnit($date=null)
    {
        return $date ? GenericTemporalUnit::createFromFormat('Y-m-d H:i:s', $date) : new GenericTemporalUnit;
    }

}
