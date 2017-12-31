<?php

namespace Ems\Expression;

use Ems\Contracts\Core\Exceptions\TypeException;
use Ems\Core\Expression;
use Ems\Core\KeyExpression;
use Ems\TestCase;
use InvalidArgumentException;

class MatcherTest extends TestCase
{
    public function test_instance()
    {
        $this->assertInstanceOf(
            Matcher::class,
            $this->newMatcher()
        );
    }

    public function test_matches_Constraint()
    {
        $matcher = $this->newMatcher();
        $this->assertTrue($matcher->matches('foo', new Constraint('equals', ['foo'])));
        $this->assertFalse($matcher->matches('foo', new Constraint('equals', ['foa'])));
    }

    public function test_matches_ConstraintGroup()
    {
        $matcher = $this->newMatcher();

        $this->assertTrue($matcher->matches(4, new ConstraintGroup('required|min:3|max:5')));
        $this->assertFalse($matcher->matches(4, new ConstraintGroup('required|min:1|max:3')));
    }

    public function test_matches_Condition()
    {

        $constraint = new Constraint('equals', ['foo']);
        $condition = new Condition(new KeyExpression('bar'), $constraint);

        $matcher = $this->newMatcher();

        $data = [
            'bar' => 'foo'
        ];

        $this->assertTrue($matcher->matches($data, $condition));
        $this->assertFalse($matcher->matches([], $condition));

        $data = [
            'bar' => 'baz'
        ];

        $this->assertFalse($matcher->matches($data, $condition));


    }

    public function test_matches_ConditionGroup_and()
    {

        $constraint = new Constraint('equals', ['foo']);
        $constraint2 = new Constraint('in', ['hihi', 'hoho']);

        $condition = new Condition(new KeyExpression('bar'), $constraint);
        $condition2 = new Condition(new KeyExpression('haha'), $constraint2);

        $group = new ConditionGroup([$condition, $condition2]);

        $matcher = $this->newMatcher();

        $data = [
            'bar' => 'foo',
            'haha' => 'hihi'
        ];

        $this->assertTrue($matcher->matches($data, $group));
        $this->assertFalse($matcher->matches([], $group));

        $data = [
            'bar' => 'baz',
            'haha' => 'huhu'
        ];

        $this->assertFalse($matcher->matches($data, $group));


    }

    public function test_matches_ConditionGroup_or()
    {

        $constraint = new Constraint('equals', ['foo']);
        $constraint2 = new Constraint('in', ['hihi', 'hoho']);

        $condition = new Condition(new KeyExpression('bar'), $constraint);
        $condition2 = new Condition(new KeyExpression('haha'), $constraint2);

        $group = new ConditionGroup([$condition, $condition2],'or');

        $matcher = $this->newMatcher();

        $data = [
            'bar' => 'foo',
            'haha' => 'hihi'
        ];

        $this->assertTrue($matcher->matches($data, $group));
        $this->assertFalse($matcher->matches([], $group));

        $data = [
            'bar' => 'foo',
            'haha' => 'huhu'
        ];

        $this->assertTrue($matcher->matches($data, $group));

        $data = [
            'bar' => 'baz',
            'haha' => 'huhu'
        ];

        $this->assertFalse($matcher->matches($data, $group));

    }

    public function test_simple_wheres()
    {
        $data = [
            'foo' => 'bar',
            'name' => 'Bill',
            'buddy' => [
                'name' => 'Tim',
                'age' => 35
            ],
            'married' => true,
            'address' => [
                'street' => 'Elm Street 14',
                'postcode' => '76148',
                'country' => [
                    'name' => 'Germany',
                    'iso'  => 'DE'
                ]
            ],
            'category_ids' => [13, 57]
        ];

        $matcher = $this->newMatcher();
        $this->assertTrue($matcher->where('foo', 'bar')->matches($data));
        $this->assertFalse($matcher->where('foo', 'boing')->matches($data));

        $this->assertFalse($matcher->where('foo', 'boing')->matches());

        $this->assertTrue($matcher->where(1, '<', 2)->matches($data));

        $this->assertTrue($matcher->where(new Condition(new KeyExpression('address.country.iso'), new Constraint('ends_with', ['E'])))->matches($data));

        $this->assertTrue($matcher->where('foo', 'bar')
                                  ->where('name', 'like', '%il%')
                                  ->matches($data)
        );

        $this->assertFalse($matcher->where('foo', 'bar')
                                   ->where('name', 'like', '%ol%')
                                   ->matches($data)
        );

        $this->assertTrue($matcher->where('foo', 'like', 'ba%')
                                  ->where('buddy.age', '>', 34)
                                  ->matches($data)
        );

        $this->assertFalse($matcher->where('foo', 'like', 'ba%')
                                   ->where('buddy.age', '>', 35)
                                   ->matches($data)
        );


    }

    /**
     * @expectedException \Ems\Core\Exceptions\NotImplementedException
     */
    public function test_matching_not_and_or_or_throws_exception()
    {
        $group = new ConditionGroup([],'nor');

        $this->newMatcher()->matches([], $group);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function test_matches_throws_exception_on_wrong_type()
    {
        $matcher = $this->newMatcher();
        $matcher->matches('foo', new KeyExpression());

    }



    protected function newMatcher()
    {
        return new Matcher;
    }
}
