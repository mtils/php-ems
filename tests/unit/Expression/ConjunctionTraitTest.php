<?php

namespace Ems\Expression;

use Ems\Contracts\Expression\Condition as ConditionContract;
use Ems\Contracts\Expression\LogicalGroup as LogicalGroupContract;
use Ems\Core\Expression;
use Ems\Core\Support\StringableTrait;
use InvalidArgumentException;

class LogicalGroupTraitTest extends \Ems\TestCase
{
    public function test_implements_interface()
    {
        $this->assertInstanceOf(
            LogicalGroupContract::class,
            $this->c()
        );
    }

    public function test_operator()
    {
        $c = $this->c();
        $this->assertEquals('and', $c->operator());
        $this->assertSame($c, $c->setOperator('or'));
        $this->assertEquals('or', $c->operator());
    }

    public function test_setOperator_throws_exception_without_unknown_operator()
    {
        $this->expectException(InvalidArgumentException::class);
        $c = $this->c();
        $c->setOperator('foo');
    }

    public function test_add_and_remove_expressions()
    {
        $c = $this->c();
        $this->assertCount(0, $c->expressions());
        $e = $this->e('foo');
        $this->assertSame($c, $c->add($e));
        $this->assertCount(1, $c->expressions());
        $this->assertSame($e, $c->expressions()[0]);
        $this->assertSame($c, $c->remove($e));
        $this->assertCount(0, $c->expressions());
    }

    public function test_clear_removes_expressions()
    {
        $c = $this->c();
        $this->assertCount(0, $c->expressions());
        $this->assertSame($c, $c->add($this->e('foo')));
        $this->assertCount(1, $c->expressions());
        $this->assertSame($c, $c->add($this->e('bar')));
        $this->assertCount(2, $c->expressions());
        $this->assertSame($c, $c->add($this->e('baz')));
        $this->assertCount(3, $c->expressions());
        $this->assertSame($c, $c->clear());
        $this->assertCount(0, $c->expressions());
    }

    protected function c()
    {
        return new LogicalGroupTraitTest_LogicalGroup;
    }

    protected function e($string)
    {
        return new Expression($string);
    }
}

class LogicalGroupTraitTest_LogicalGroup implements LogicalGroupContract
{
    use LogicalGroupTrait;
    use StringableTrait;

    public function toString()
    {
        return '';
    }
}
