<?php

namespace Ems\Core;

use Ems\Contracts\Core\None;
use Ems\Contracts\Core\PointInTime as PointInTimeContract;

class PointInTimeTest extends \Ems\TestCase
{
    public function test_implements_interface()
    {
        $this->assertInstanceOf(
            PointInTimeContract::class,
            $this->time()
        );
    }

    public function test_year_property()
    {
        $unit = $this->time('2016-05-31 12:32:14');

        $this->assertEquals(2016, $unit->year);

        $unit->year = 2014;

        $this->assertEquals(2014, $unit->year);
    }

    public function test_month_property()
    {
        $unit = $this->time('2016-05-15 12:32:14');

        $this->assertEquals(5, $unit->month);

        $unit->month = 6;

        $this->assertEquals(6, $unit->month);
    }

    public function test_precision()
    {
        $time = $this->time();
        $this->assertEquals(PointInTimeContract::SECOND, $time->precision());
        $this->assertSame($time, $time->setPrecision(PointInTimeContract::DAY));
        $this->assertEquals(PointInTimeContract::DAY, $time->precision());
    }

    public function test_invalidate()
    {
        $this->assertTrue($this->time()->isValid());
        $this->assertFalse((new PointInTime(new None))->isValid());
    }

    /**
     * @param null $date
     *
     * @return PointInTime
     */
    protected function time($date=null)
    {
        return $date ? PointInTime::createFromFormat('Y-m-d H:i:s', $date) : new PointInTime();
    }
}
