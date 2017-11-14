<?php

namespace Ems\Expression;

use Ems\Contracts\Expression\Constraint as ConstraintContract;

class ConstraintTest extends \Ems\TestCase
{
    public function test_implements_interface()
    {
        $this->assertInstanceOf(
            ConstraintContract::class,
            $this->constraint('min')
        );
    }

    public function test_name()
    {
        $c = $this->constraint('max');
        $this->assertEquals('max', $c->name());
        $this->assertSame($c, $c->setName('min'));
        $this->assertEquals('min', $c->name());
    }

    public function test_parameters()
    {
        $parameters = [5];
        $c = $this->constraint('max', $parameters);
        $this->assertEquals($parameters, $c->parameters());
        $this->assertSame($c, $c->setParameters([6]));
        $this->assertEquals([6], $c->parameters());
    }

    public function test_operator()
    {
        $operator = '<=';
        $parameters = [5];
        $c = $this->constraint('max', $parameters, $operator);
        $this->assertEquals($operator, $c->operator());
        $this->assertSame($c, $c->setOperator('<'));
        $this->assertEquals('<', $c->operator());
    }

    public function test_toStringFormat()
    {
        $operator = '<=';
        $parameters = [5];
        $c = $this->constraint('max', $parameters, $operator, 'name');
        $this->assertEquals('name', $c->getToStringFormat());
        $this->assertSame($c, $c->setToStringFormat('operator'));
        $this->assertEquals('operator', $c->getToStringFormat());
    }

    /**
     * @expectedException InvalidArgumentException
     **/
    public function test_setToStringFormat_with_unknown_format_throws_exception()
    {
        $c = $this->constraint('max');
        $c->setToStringFormat('foo');
    }

    public function test___toString_with_operators()
    {

        $c = $this->constraint('max', [], '>=');
        $this->assertEquals('>=', "$c");

        $c = $this->constraint('min', [15], '>=');
        $this->assertEquals('>= 15', "$c");

        $c = $this->constraint('not', [null], 'IS NOT');
        $this->assertEquals('IS NOT null', "$c");

        $resource = fopen(__FILE__, 'r');
        $type = get_resource_type($resource);
        $c = $this->constraint('resource', [$resource], 'is a');
        $this->assertEquals("is a resource of type $type", "$c");
        fclose($resource);

        $c = $this->constraint('is_a', [new \stdClass], 'instanceof');
        $this->assertEquals("instanceof stdClass", "$c");

        $c = $this->constraint('in_array', [[1,5,7]], 'IN');
        $this->assertEquals("IN (1, 5, 7)", "$c");

        $c = $this->constraint('SUM', ['amount', 'quantity'], '');
        $this->assertEquals("SUM(amount, quantity)", "$c");


    }

    public function test___toString_with_names()
    {

        $c = $this->constraint('max', [], '>=', 'name');
        $this->assertEquals('max', "$c");

        $c = $this->constraint('min', [15], '>=', 'name');
        $this->assertEquals('min:15', "$c");

        $c = $this->constraint('not', [null], 'IS NOT', 'name');
        $this->assertEquals('not:null', "$c");

        $resource = fopen(__FILE__, 'r');
        $type = get_resource_type($resource);
        $c = $this->constraint('resource', [$resource], 'is a', 'name');
        $this->assertEquals("resource:$type", "$c");
        fclose($resource);

        $c = $this->constraint('is_a', [new \stdClass], 'instanceof', 'name');
        $this->assertEquals("is_a:stdClass", "$c");

        $c = $this->constraint('in', [[1,5,7]], 'IN', 'name');
        $this->assertEquals("in:1,5,7", "$c");

        $bigIn = range(0,90);
        $binInString = implode(',', $bigIn);
        $c = $this->constraint('in', [$bigIn], 'IN', 'name');

        $this->assertEquals("in:[$binInString]", "$c");

        $c = $this->constraint('is_a', [[new \stdClass, new \ArrayObject]], 'instanceof', 'name');
        $this->assertEquals("is_a:[stdClass,ArrayObject]", "$c");


    }

    /**
     * @expectedException BadMethodCallException
     **/
    public function test_allowOperators_twice_throws_exception()
    {
        $operator = '<=';
        $parameters = [5];
        $this->constraint('max', $parameters, $operator)->allowOperators('<=')->allowOperators('=');
    }

    /**
     * @expectedException Ems\Contracts\Core\Errors\UnSupported
     **/
    public function test_operator_throws_exception_if_not_allowed()
    {
        $operator = '<=';
        $parameters = [5];
        $c = $this->constraint('max', $parameters, $operator)->allowOperators('<=');
        $this->assertEquals(['<='], $c->allowedOperators());
        $this->assertEquals($operator, $c->operator());
        $this->assertSame($c, $c->setOperator('<'));
    }

    /**
     * @expectedException Ems\Contracts\Core\Errors\UnSupported
     **/
    public function test_allowOperator_throws_exception_if_current_operator_not_allowed()
    {
        $operator = '<';
        $parameters = [5];
        $c = $this->constraint('max', $parameters, $operator)->allowOperators('<=');
    }

    public function constraint($name, $parameters=[], $operator='', $toStringFormat='operator')
    {
        return new Constraint($name, $parameters, $operator, $toStringFormat);
    }
}
