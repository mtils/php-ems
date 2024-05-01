<?php

namespace Ems\Expression;

use BadMethodCallException;
use Ems\Contracts\Core\Errors\UnSupported;
use Ems\Contracts\Expression\Condition as ConditionContract;
use Ems\Core\Expression;
use InvalidArgumentException;

class ConditionTest extends \Ems\TestCase
{
    public function test_implements_interface()
    {
        $this->assertInstanceOf(
            ConditionContract::class,
            $this->condition()
        );
    }

    public function test_operand()
    {

        $c = $this->condition();
        $this->assertSame($c, $c->setOperand('first_name'));
        $this->assertEquals('first_name', $c->operand());
    }

    public function test_setOperand_throws_exception_if_unsupported_type()
    {
        $this->expectException(InvalidArgumentException::class);
        $c = $this->condition();
        $c->setOperand(new \stdClass);
    }

    public function test_constraint()
    {
        $c = $this->condition();
        $constraint = new Constraint('required');
        $this->assertSame($c, $c->setConstraint($constraint));
        $this->assertSame($constraint, $c->constraint());
    }

    public function test_setConstraint_throws_exception_if_unsupported_type()
    {
        $this->expectException(InvalidArgumentException::class);
        $c = $this->condition();
        $c->setConstraint(new \stdClass);
    }

    public function test_expressions_with_constraint()
    {
        $c = $this->condition();
        $constraint = new Constraint('required');
        $this->assertSame($c, $c->setConstraint($constraint));
        $this->assertSame($constraint, $c->constraint());
        $this->assertCount(1, $c->expressions());
        $this->assertSame($constraint, $c->expressions()[0]);
    }

    public function test_expressions_with_operand_and_constraint()
    {

        $constraint = new Constraint('required');
        $operand = new Expression('login');

        $c = $this->condition($operand, $constraint);

        $this->assertSame($constraint, $c->constraint());
        $this->assertCount(2, $c->expressions());
        $this->assertSame($operand, $c->expressions()[0]);
        $this->assertSame($constraint, $c->expressions()[1]);
    }

    public function test___toString_returns_expected_string()
    {
        $c = $this->condition(5, new Constraint('min', [3], '>='));
        $this->assertEquals('5 >= 3', "$c");
    }

    public function test_allowOperators_twice_throws_exception()
    {
        $this->expectException(BadMethodCallException::class);
        $this->condition()->allowOperators('=')->allowOperators('!=');
    }

    public function test_expressions_with_unsupported_operator_throws_exception()
    {
        $this->expectException(UnSupported::class);
        $c = $this->condition()->allowOperators('=');
        $this->assertEquals(['='], $c->allowedOperators());
        $constraint = new Constraint('required');
        $c->setConstraint($constraint);
    }

    public function test_expressions_with_supported_operator_throws_no_exception()
    {
        $c = $this->condition()->allowOperators('=');
        $this->assertEquals(['='], $c->allowedOperators());
        $constraint = new Constraint('equals', [], '=');
        $c->setConstraint($constraint);
    }

    public function test_expressions_with_unsupported_operator_throws_exception_if_constraint_already_added()
    {
        $this->expectException(UnSupported::class);
        $c = $this->condition('login', new Constraint('required'));
        $c->allowOperators('=');
    }

    public function test_expressions_with_supported_operator_throws_no_exception_if_constraint_already_added()
    {
        $c = $this->condition('login', new Constraint('equals', [], '='));
        $c->allowOperators('=');
    }


    public function condition($operand=null, $constraint=null)
    {
        return new Condition($operand, $constraint);
    }
}
