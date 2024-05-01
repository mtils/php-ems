<?php
/**
 *  * Created by mtils on 11.09.18 at 14:31.
 **/

namespace Ems\Core;


use Ems\Contracts\Core\IdGenerator;
use Ems\TestCase;
use Ems\Testing\Cheat;
use PHPUnit\Framework\Attributes\Test;

use function func_get_args;
use const PHP_INT_MAX;

class AbstractIdGeneratorTest extends TestCase
{
    #[Test] public function implements_interface()
    {
        $this->assertInstanceOf(IdGenerator::class, $this->newGenerator());
    }

    #[Test] public function idType_returns_int()
    {
        $generator = $this->newGenerator();
        $this->assertEquals(Cheat::get($generator, 'idType'), $generator->idType());
    }

    #[Test] public function min_returns_correct_value()
    {
        $generator = $this->newGenerator();
        $this->assertEquals(Cheat::get($generator, 'min'), $generator->min());
    }

    #[Test] public function max_returns_correct_value()
    {
        $generator = $this->newGenerator();
        $this->assertEquals(Cheat::get($generator, 'max'), $generator->max());
    }

    #[Test] public function strength_correct_value()
    {
        $generator = $this->newGenerator();
        $this->assertEquals(Cheat::get($generator, 'strength'), $generator->strength());
    }

    #[Test] public function isSupported_returns_true()
    {
        $generator = $this->newGenerator();
        $this->assertTrue($generator->isSupported());
    }

    #[Test] public function generate_is_called()
    {
        $generator = $this->newGenerator();
        $args = ['foo', 45, false];

        $generator->handler = function ($salt, $length, $asciiOnly) use ($args) {
            $this->assertEquals($args[0], $salt);
            $this->assertEquals($args[2], $asciiOnly);
            return $length;
        };

        $this->assertEquals(45, $generator->generate($args[0], $args[1], $args[2]));

    }

    #[Test] public function generate_is_called_until_id_not_known()
    {
        $generator = $this->newGenerator();

        $args = ['foo', 45, false];

        $currentId = 0;

        $generator->handler = function () use ($args, &$currentId) {
            $currentId++;
            return $currentId;
        };

        $uniqueChecker = function ($id){
            return $id >= 10;
        };

        $copy = $generator->until($uniqueChecker);

        $copy->handler = $generator->handler;
        $this->assertEquals(10, $copy->generate());
    }

    #[Test] public function test_generate_throws_exception_after_too_many_tries()
    {
        $this->expectException(
            \Ems\Contracts\Core\Exceptions\TooManyIterationsException::class
        );
        $generator = $this->newGenerator();

        $args = ['foo', 45, false];

        $currentId = 0;

        $generator->handler = function () use ($args, &$currentId) {
            $currentId++;
            return $currentId;
        };

        $uniqueChecker = function ($id){
            return $id >= 1000;
        };

        $copy = $generator->until($uniqueChecker);

        $copy->setMaxAttempts(100);

        $copy->handler = $generator->handler;
        $copy->generate();
    }

    public function newGenerator()
    {
        return new AbstractIdGeneratorTest_Generator();
    }
}

class AbstractIdGeneratorTest_Generator extends AbstractIdGenerator
{

    protected $min = 1;

    protected $max = PHP_INT_MAX;

    protected $idType = 'int';

    /**
     * @var callable
     */
    public $handler;

    /**
     * @inheritDoc
     */
    protected function generateFresh(
        $salt = null,
        $length = 0,
        $asciiOnly = true
    ) {
        if ($this->handler) {
            return call_user_func($this->handler, ...func_get_args());
        }
        return $salt;
    }

    public function setMaxAttempts($attempts)
    {
        $this->maxAttempts = $attempts;
    }
}