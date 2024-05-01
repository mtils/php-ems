<?php
/**
 *  * Created by mtils on 22.02.20 at 12:14.
 **/

namespace Ems\Contracts\Model\Database;

use Countable;
use Ems\Contracts\Expression\Queryable;
use Ems\Core\Exceptions\UnsupportedParameterException;
use Ems\TestCase;
use IteratorAggregate;
use stdClass;

use function iterator_to_array;


class ParenthesesTest extends TestCase
{

    /**
     * @test
     */
    public function it_implements_interfaces()
    {
        $test = $this->newParentheses();
        $this->assertInstanceOf(IteratorAggregate::class, $test);
        $this->assertInstanceOf(Queryable::class, $test);
        $this->assertInstanceOf(Countable::class, $test);
    }

    /**
     * @test
     */
    public function where_adds_predicate()
    {
        $operand = 'user';
        $value = 'michael';

        $test = $this->newParentheses();
        $this->assertSame($test, $test->where($operand, $value));
        $first = $test->first();
        $this->assertInstanceOf(Predicate::class, $first);
        $this->assertEquals($operand, $first->left);
        $this->assertEquals('=', $first->operator);
        $this->assertEquals($value, $first->right);
    }

    /**
     * @test
     */
    public function where_adds_custom_predicate()
    {
        $predicate = new Predicate();
        $test = $this->newParentheses();
        $this->assertSame($test, $test->where($predicate));
        $first = $test->first();
        $this->assertSame($predicate, $first);
    }

    /**
     * @test
     *
     */
    public function test_where_with_unsupported_type_throws_exception()
    {
        $this->expectException(UnsupportedParameterException::class);
        $predicate = new Predicate();
        $test = $this->newParentheses();
        $test->where(new stdClass());
    }

    /**
     * @test
     */
    public function invoke_creates_sub_parentheses()
    {
        $test = $this->newParentheses('AND');

        $test->where('age', 33)
             ->where('birthday', null);

        $groupRef = $test('OR', function (Parentheses $group) {
            $group->where('mother', 'Conny')
                  ->where('father', 'Peter');
        });

        $this->assertEquals('AND', $test->boolean);
        $this->assertEquals('age', $test->expressions[0]->left);
        $this->assertEquals('birthday', $test->expressions[1]->left);

        $inner = $test->expressions[2];

        $this->assertSame($groupRef, $inner);
        $this->assertEquals('OR', $inner->boolean);
        $this->assertEquals('mother', $inner->expressions[0]->left);
        $this->assertEquals('father', $inner->expressions[1]->left);
    }

    /**
     * @test
     */
    public function clear_clears_expressions()
    {
        $test = $this->newParentheses();

        $test->where('age', 33)
             ->where('birthday', null);

        $this->assertCount(2, $test);

        $test->clear();

        $this->assertCount(0, $test);

    }

    /**
     * @test
     */
    public function getIterator_returns_expressions()
    {
        $test = $this->newParentheses();

        $test->where('age', 33)
             ->where('birthday', null);

        $expressions = iterator_to_array($test);

        $this->assertSame($expressions[0], $test->expressions[0]);
        $this->assertSame($expressions[1], $test->expressions[1]);
    }

    /**
     * @test
     */
    public function __get_unknown_property()
    {
        $this->assertNull($this->newParentheses()->__get('foo'));
    }

    /**
     * @param string $boolean
     * @param array $expressions
     *
     * @return Parentheses
     */
    protected function newParentheses($boolean='', $expressions=[])
    {
        return new Parentheses($boolean, $expressions);
    }
}