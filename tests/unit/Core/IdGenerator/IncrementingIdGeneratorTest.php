<?php
/**
 *  * Created by mtils on 11.09.18 at 14:31.
 **/

namespace Ems\Core\IdGenerator;


use Ems\Contracts\Core\IdGenerator;
use Ems\TestCase;

class IncrementingIdGeneratorTest extends TestCase
{
    /**
     * @test
     */
    public function implements_interface()
    {
        $this->assertInstanceOf(IdGenerator::class, $this->newGenerator());
    }

    /**
     * @test
     */
    public function setMin_changes_min()
    {
        $generator = $this->newGenerator();
        $generator->setMin(15);
        $this->assertEquals(15, $generator->min());
    }

    /**
     * @test
     * @expectedException \Ems\Core\Exceptions\UnsupportedParameterException
     */
    public function setMin_throws_exception_if_not_numeric()
    {
        $generator = $this->newGenerator();
        $generator->setMin('Heinz der Würdevolle');
    }

    /**
     * @test
     * @expectedException \Ems\Core\Exceptions\UnsupportedParameterException
     */
    public function setMin_throws_exception_greater_then_max()
    {
        $generator = $this->newGenerator();
        $generator->setMax(100);
        $generator->setMin(200);
    }

    /**
     * @test
     */
    public function setMax_changes_max()
    {
        $generator = $this->newGenerator();
        $generator->setMax(150);
        $this->assertEquals(150, $generator->max());
    }

    /**
     * @test
     * @expectedException \Ems\Core\Exceptions\UnsupportedParameterException
     */
    public function setMax_throws_exception_if_not_numeric()
    {
        $generator = $this->newGenerator();
        $generator->setMax('Franz die Schönheit');
    }

    /**
     * @test
     * @expectedException \Ems\Core\Exceptions\UnsupportedParameterException
     */
    public function setMax_throws_exception_smaller_then_min()
    {
        $generator = $this->newGenerator();
        $generator->setMin(100);
        $generator->setMax(50);
    }

    /**
     * @test
     */
    public function generateFresh_creates_next_int_without_salt()
    {
        $generator = $this->newGenerator();

        $this->assertEquals(1, $generator->generate());

        $ids = [];

        $valid = function ($id) use (&$ids) {
            $ids[] = $id;
            return $id > 10;
        };

        $this->assertEquals(11, $generator->until($valid)->generate());
        $this->assertEquals(range(1,11), $ids);

    }

    /**
     * @test
     * @expectedException \Ems\Core\Exceptions\UnsupportedUsageException
     *
     */
    public function passing_length_to_generate_fails()
    {
        $this->newGenerator()->generate(null, 45);
    }

    /**
     * @test
     * @expectedException \Ems\Core\Exceptions\UnsupportedParameterException
     */
    public function passing_non_numeric_salt_fails()
    {
        $this->newGenerator()->generate('hans', 0);
    }

    /**
     * @test
     */
    public function passing_salt_more_or_equals_real_min_does_not_fail()
    {
        $this->newGenerator()->generate(0, 0);
    }

    /**
     * @test
     * @expectedException \OutOfBoundsException
     */
    public function passing_salt_less_than_min_fails()
    {
        $this->newGenerator()->setMin(3)->generate(1, 0);
    }

    /**
     * @test
     * @expectedException \OutOfBoundsException
     */
    public function passing_salt_greater_than_max_fails()
    {
        $this->newGenerator()->setMax(10)->generate(12, 0);
    }

    /**
     * @test
     */
    public function passing_salt_leads_to_next_integer()
    {
        $generator = $this->newGenerator();

        $this->assertEquals(2, $generator->generate(1));
        $this->assertEquals(3, $generator->generate(2));
        $this->assertEquals(300, $generator->generate(299));
    }

    /**
     * @test
     * @expectedException \OverflowException
     */
    public function generating_id_greater_than_max_fails()
    {
        $this->newGenerator()->setMax(10)->generate(10);
    }

    public function newGenerator()
    {
        return new IncrementingIdGenerator();
    }
}