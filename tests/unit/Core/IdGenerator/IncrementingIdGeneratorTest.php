<?php
/**
 *  * Created by mtils on 11.09.18 at 14:31.
 **/

namespace Ems\Core\IdGenerator;


use Ems\Contracts\Core\IdGenerator;
use Ems\TestCase;
use PHPUnit\Framework\Attributes\Test;

class IncrementingIdGeneratorTest extends TestCase
{
    #[Test] public function implements_interface()
    {
        $this->assertInstanceOf(IdGenerator::class, $this->newGenerator());
    }

    #[Test] public function setMin_changes_min()
    {
        $generator = $this->newGenerator();
        $generator->setMin(15);
        $this->assertEquals(15, $generator->min());
    }

    #[Test] public function test_setMin_throws_exception_if_not_numeric()
    {
        $this->expectException(
            \Ems\Core\Exceptions\UnsupportedParameterException::class
        );
        $generator = $this->newGenerator();
        $generator->setMin('Heinz der Würdevolle');
    }

    /**
     *
     */
    public function test_setMin_throws_exception_greater_then_max()
    {
        $this->expectException(
            \Ems\Core\Exceptions\UnsupportedParameterException::class
        );
        $generator = $this->newGenerator();
        $generator->setMax(100);
        $generator->setMin(200);
    }

    #[Test] public function setMax_changes_max()
    {
        $generator = $this->newGenerator();
        $generator->setMax(150);
        $this->assertEquals(150, $generator->max());
    }

    public function test_setMax_throws_exception_if_not_numeric()
    {
        $this->expectException(
            \Ems\Core\Exceptions\UnsupportedParameterException::class
        );
        $generator = $this->newGenerator();
        $generator->setMax('Franz die Schönheit');
    }

    public function test_setMax_throws_exception_smaller_then_min()
    {
        $this->expectException(
            \Ems\Core\Exceptions\UnsupportedParameterException::class
        );
        $generator = $this->newGenerator();
        $generator->setMin(100);
        $generator->setMax(50);
    }

    #[Test] public function test_generateFresh_creates_next_int_without_salt()
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

    public function test_passing_length_to_generate_fails()
    {
        $this->expectException(
            \Ems\Core\Exceptions\UnsupportedUsageException::class
        );
        $this->newGenerator()->generate(null, 45);
    }

    public function test_passing_non_numeric_salt_fails()
    {
        $this->expectException(
            \Ems\Core\Exceptions\UnsupportedParameterException::class
        );
        $this->newGenerator()->generate('hans', 0);
    }

    #[Test] public function passing_salt_more_or_equals_real_min_does_not_fail()
    {
        $this->newGenerator()->generate(0, 0);
    }

    #[Test] public function passing_salt_less_than_min_fails()
    {
        $this->expectException(\OutOfBoundsException::class);
        $this->newGenerator()->setMin(3)->generate(1, 0);
    }

    #[Test] public function passing_salt_greater_than_max_fails()
    {
        $this->expectException(\OutOfBoundsException::class);
        $this->newGenerator()->setMax(10)->generate(12, 0);
    }

    #[Test] public function passing_salt_leads_to_next_integer()
    {
        $generator = $this->newGenerator();

        $this->assertEquals(2, $generator->generate(1));
        $this->assertEquals(3, $generator->generate(2));
        $this->assertEquals(300, $generator->generate(299));
    }

    #[Test] public function generating_id_greater_than_max_fails()
    {
        $this->expectException(\OverflowException::class);
        $this->newGenerator()->setMax(10)->generate(10);
    }

    public function newGenerator()
    {
        return new IncrementingIdGenerator();
    }
}