<?php


namespace Ems\Expression;

use BadMethodCallException;
use Ems\Contracts\Core\Errors\SyntaxError;
use Ems\Contracts\Core\Errors\UnSupported;
use Ems\Contracts\Expression\Condition as ConditionContract;
use Ems\Contracts\Expression\ConditionGroup as ConditionGroupContract;
use Ems\Core\Exceptions\NotImplementedException;
use Ems\Core\Expression;
use Ems\Core\KeyExpression;
use Ems\Testing\Cheat;
use Ems\Core\Collections\StringList;
use InvalidArgumentException;
use OutOfBoundsException;

class ConditionGroupTest extends \Ems\TestCase
{
    public function test_implements_interface()
    {
        $this->assertInstanceOf(ConditionGroupContract::class, $this->newGroup());
    }

    public function test_addWhere_adds_condition()
    {
        $group = $this->newGroup();

        $this->assertCount(0, $group->conditions());

        $group = $group->where('login', 'dieter');

        $this->assertCount(1, $group->conditions());

        $condition = $group->conditions()[0];

        $this->assertInstanceOf(KeyExpression::class, $condition->operand());
        $this->assertEquals('login', (string)$condition->operand());
        $this->assertEquals('dieter', $condition->constraint()->parameters()[0]);
        $this->assertEquals('=', $condition->constraint()->operator());
        $this->assertEquals('equal', $condition->constraint()->name());

    }

    public function test_whereNot_adds_condition()
    {
        $group = $this->newGroup();

        $this->assertCount(0, $group->conditions());

        $group = $group->whereNot('login', 'dieter');

        $this->assertCount(1, $group->conditions());

        $condition = $group->conditions()[0];

        $this->assertEquals('nand', $group->operator());
        $this->assertInstanceOf(KeyExpression::class, $condition->operand());
        $this->assertEquals('login', (string)$condition->operand());
        $this->assertEquals('dieter', $condition->constraint()->parameters()[0]);
        $this->assertEquals('=', $condition->constraint()->operator());
        $this->assertEquals('equal', $condition->constraint()->name());

    }

    public function test_orWhere()
    {
        $group = $this->newGroup();

        $this->assertCount(0, $group->conditions());

        $group = $group->where('login', 'dieter');

        $this->assertCount(1, $group->conditions());

        $condition = $group->conditions()[0];

        $this->assertInstanceOf(KeyExpression::class, $condition->operand());
        $this->assertEquals('login', (string)$condition->operand());
        $this->assertEquals('dieter', $condition->constraint()->parameters()[0]);
        $this->assertEquals('=', $condition->constraint()->operator());
        $this->assertEquals('equal', $condition->constraint()->name());

        $group = $group->orWhere('login', 'helmut');

        $condition = $group->conditions()[1];

        // An or after an and makes the whole thing an or
        $this->assertEquals('or', $group->operator());
        $this->assertCount(2, $group->expressions());

    }

    public function test_whereNone()
    {
        $group = $this->newGroup();

        $this->assertCount(0, $group->conditions());

        $group = $group->where('login', 'dieter');

        $this->assertCount(1, $group->conditions());

        $condition = $group->conditions()[0];

        $this->assertInstanceOf(KeyExpression::class, $condition->operand());
        $this->assertEquals('login', (string)$condition->operand());
        $this->assertEquals('dieter', $condition->constraint()->parameters()[0]);
        $this->assertEquals('=', $condition->constraint()->operator());
        $this->assertEquals('equal', $condition->constraint()->name());

        $group = $group->whereNone('login', 'dieter');

        $condition = $group->conditions()[0];

        // An or after an and makes the whole thing an or
        $this->assertEquals('nor', $group->operator());
        $this->assertCount(2, $group->expressions());

        $condition1 = $group->conditions()[0];
        $condition2 = $group->conditions()[1];

        $this->assertInstanceOf(ConditionContract::class, $condition1);
        $this->assertInstanceOf(ConditionContract::class, $condition2);

    }

    public function test_addWhere_adds_condition_with_operator()
    {
        $group = $this->newGroup();

        $this->assertCount(0, $group->conditions());

        $group = $group->where('age', '<=', 75);

        $this->assertCount(1, $group->conditions());

        $condition = $group->conditions()[0];

        $this->assertInstanceOf(KeyExpression::class, $condition->operand());
        $this->assertEquals('age', (string)$condition->operand());
        $this->assertEquals(75, $condition->constraint()->parameters()[0]);
        $this->assertEquals('<=', $condition->constraint()->operator());
        $this->assertEquals('max', $condition->constraint()->name());

    }

    public function test_addWhere_adds_condition_with_numeric_operand()
    {
        $group = $this->newGroup();

        $this->assertCount(0, $group->conditions());

        $group = $group->where(1, '<=', 5);

        $this->assertCount(1, $group->conditions());

        $condition = $group->conditions()[0];

        $this->assertInstanceOf(Expression::class, $condition->operand());
        $this->assertNotInstanceOf(KeyExpression::class, $condition->operand());
        $this->assertEquals('1', (string)$condition->operand());
        $this->assertEquals(5, $condition->constraint()->parameters()[0]);
        $this->assertEquals('<=', $condition->constraint()->operator());
        $this->assertEquals('max', $condition->constraint()->name());

    }

    public function test_addWhere_adds_condition_with_expression()
    {
        $group = $this->newGroup();

        $this->assertCount(0, $group->conditions());

        $expression = new Expression('23');
        $group = $group->where($expression, '<=', 5);

        $this->assertCount(1, $group->conditions());

        $condition = $group->conditions()[0];

        $this->assertSame($expression, $condition->operand());
        $this->assertEquals('23', (string)$condition->operand());
        $this->assertEquals(5, $condition->constraint()->parameters()[0]);
        $this->assertEquals('<=', $condition->constraint()->operator());
        $this->assertEquals('max', $condition->constraint()->name());

    }

    public function test_addWhere_adds_condition_with_constraint()
    {
        $group = $this->newGroup();

        $this->assertCount(0, $group->conditions());

        $constraint = new Constraint('min', [16], '>=');
        $group = $group->where('age', $constraint);

        $this->assertCount(1, $group->conditions());

        $condition = $group->conditions()[0];

        $this->assertSame($constraint, $condition->constraint());
        $this->assertEquals('age', (string)$condition->operand());
        $this->assertEquals(16, $condition->constraint()->parameters()[0]);
        $this->assertEquals('>=', $condition->constraint()->operator());
        $this->assertEquals('min', $condition->constraint()->name());

    }

    public function test_addWhere_adds_condition_with_closure()
    {
        $group = $this->newGroup();

        $group = $group->where('login', 'dieter')
                       ->orWhere(function (ConditionGroup $group) {
                        return $group->where('name', 'LIKE', 'helmut')
                                     ->where('age', '>', 6);
                       });

        $this->assertCount(2, $group->conditions());

        $condition = $group->conditions()[0];

        $this->assertEquals('or', $group->operator());

        $this->assertInstanceOf(KeyExpression::class, $condition->operand());
        $this->assertEquals('login', (string)$condition->operand());
        $this->assertEquals('dieter', $condition->constraint()->parameters()[0]);
        $this->assertEquals('=', $condition->constraint()->operator());
        $this->assertEquals('equal', $condition->constraint()->name());

        $subGroup = $group->conditions()[1];
        $this->assertInstanceOf(ConditionGroupContract::class, $subGroup);
        $this->assertCount(2, $subGroup->conditions());
        $this->assertEquals('and', $subGroup->operator());

        $condition = $subGroup->conditions()[0];

        $this->assertEquals('name', (string)$condition->operand());
        $this->assertEquals('helmut', $condition->constraint()->parameters()[0]);
        $this->assertEquals('LIKE', $condition->constraint()->operator());
        $this->assertEquals('like', $condition->constraint()->name());

        $condition = $subGroup->conditions()[1];

        $this->assertEquals('age', (string)$condition->operand());
        $this->assertEquals(6, $condition->constraint()->parameters()[0]);
        $this->assertEquals('>', $condition->constraint()->operator());
        $this->assertEquals('greater', $condition->constraint()->name());

    }

    public function test_addWhere_with_unsupported_argument_throws_exception()
    {
        $this->expectException(InvalidArgumentException::class);
        $group = $this->newGroup();

        $group = $group->where(new \ArrayObject);

    }

    public function test_addWhere_without_operator_throws_exception()
    {
        $this->expectException(SyntaxError::class);
        $group = $this->newGroup();

        $group = $group->where('login');

    }

    public function test_fork_throws_exception_if_not_implements()
    {
        $this->expectException(
            NotImplementedException::class
        );
        $group = new ConditionGroupTest_ConditionGroup;

        $group = $group->where('login', 'dieter');

    }

    public function test_conditions_returns_matching_expressions()
    {
        $group = $this->newGroup();

        $c = new Condition(new KeyExpression('email'), new Constraint('like', ['@gmail.com']));

        $group = $group->where('login', 'dieter')
                       ->where($c)
                       ->orWhere(function (ConditionGroup $group) {
                        return $group->where('name', 'LIKE', 'helmut')
                                     ->where('age', '>', 6);
                       });

        $result = $group->conditions('login');
        $this->assertCount(1, $result);
        $this->assertEquals('login', (string)$result[0]->operand());
        $this->assertEquals('=', (string)$result[0]->constraint()->operator());
        $this->assertEquals('dieter', (string)$result[0]->constraint()->parameters()[0]);

        $result = $group->conditions('name');
        $this->assertEquals('name', (string)$result[0]->operand());
        $this->assertEquals('LIKE', (string)$result[0]->constraint()->operator());
        $this->assertEquals('helmut', (string)$result[0]->constraint()->parameters()[0]);

    }

    public function test_hasCondition_returns_correct_value()
    {
        $group = $this->newGroup();

        $c = new Condition(new KeyExpression('email'), new Constraint('like', ['@gmail.com']));

        $group = $group->where('login', 'dieter')
                       ->where($c)
                       ->orWhere(function (ConditionGroup $group) {
                        return $group->where('name', 'LIKE', 'helmut')
                                     ->where('age', '>', 6);
                       });

        $this->assertTrue($group->hasConditions('login'));
        $this->assertTrue($group->hasConditions('name'));
        $this->assertTrue($group->hasConditions('age'));
        $this->assertFalse($group->hasConditions('foo'));

    }

    public function test_keys_returns_all_keys()
    {
        $group = $this->newGroup();

        $c = new Condition(new KeyExpression('email'), new Constraint('like', ['@gmail.com']));

        $this->assertInstanceOf(StringList::class, $group->keys());
        $this->assertCount(0, $group->keys());

        $group = $group->where('login', 'dieter');

        $this->assertTrue($group->keys()->contains('login'));
        $this->assertFalse($group->keys()->contains('name'));

        $group = $group->where($c);

        $this->assertCount(2, $group->keys());
        $this->assertTrue($group->keys()->contains('login'));
        $this->assertTrue($group->keys()->contains('email'));
        $this->assertFalse($group->keys()->contains('name'));

        $group = $group->orWhere(function (ConditionGroup $group) {
                        return $group->where('name', 'LIKE', 'helmut')
                                     ->where('age', '>', 6);
                       });


        $this->assertCount(4, $group->keys());
        $this->assertTrue($group->keys()->contains('login'));
        $this->assertTrue($group->keys()->contains('name'));
        $this->assertTrue($group->keys()->contains('email'));
        $this->assertTrue($group->keys()->contains('age'));

    }

    public function test___toString_parses_simple_expression()
    {
        $group = $this->newGroup();

        $c = new Condition(new KeyExpression('email'), new Constraint('like', ['@gmail.com'], 'LIKE', 'operator'));

        $this->assertInstanceOf(StringList::class, $group->keys());
        $this->assertCount(0, $group->keys());

        $group = $group->where('login', 'dieter');

        $this->assertEquals('login = dieter', "$group");

    }

    public function test___toString_parses_simple_expressions()
    {
        $group = $this->newGroup();

        $c = new Condition(new KeyExpression('email'), new Constraint('like', ['@gmail.com'], 'LIKE', 'operator'));

        $this->assertInstanceOf(StringList::class, $group->keys());
        $this->assertCount(0, $group->keys());

        $group = $group->where('login', 'dieter')
                       ->where('age', '>=', '40');

        $this->assertEquals('login = dieter AND age >= 40', "$group");

    }

    public function test___toString_parses_nested_expressions()
    {
        $group = $this->newGroup();

        $c = new Condition(new KeyExpression('email'), new Constraint('like', ['@gmail.com'], 'LIKE', 'operator'));

        $group = $group->where('login', 'dieter')
                       ->where($c)
                       ->orWhere(function (ConditionGroup $group) {
                        return $group->where('name', 'LIKE', 'helmut')
                                     ->where('age', '>', 6);
                       });

        $this->assertEquals('(login = dieter AND email LIKE @gmail.com) OR (name LIKE helmut AND age > 6)', "$group");

    }

    public function test_conditionBuilding_with_operator_changes_1()
    {

        $group = $this->newGroup()->where('a', 'b');

        $this->assertEquals('a', (string)$group->expressions()[0]->operand());
        $this->assertEquals('b', (string)$group->expressions()[0]->constraint()->parameters()[0]);
        $this->assertEquals('and', $group->operator());

        $group = $group->orWhere('c', 'd');
        // After this just the operator should change, because the "OR" should be
        // appended to the previous one and the previous didnt have an operator
        // with a count of 1
        $this->assertEquals('a', (string)$group->expressions()[0]->operand());
        $this->assertEquals('b', (string)$group->expressions()[0]->constraint()->parameters()[0]);
        $this->assertEquals('or', $group->operator());

        $this->assertEquals('c', (string)$group->expressions()[1]->operand());
        $this->assertEquals('d', (string)$group->expressions()[1]->constraint()->parameters()[0]);

        $group = $group->where('e', 'f');
        // After this the complete old conditiongroup should be merged into a
        // new one, the new expression was added and the operator changes to and
        $group1 = $group->expressions()[0];
        $this->assertEquals('a', (string)$group1->expressions()[0]->operand());
        $this->assertEquals('b', (string)$group1->expressions()[0]->constraint()->parameters()[0]);
        $this->assertEquals('or', $group1->operator());

        $this->assertEquals('c', (string)$group1->expressions()[1]->operand());
        $this->assertEquals('d', (string)$group1->expressions()[1]->constraint()->parameters()[0]);

        $this->assertEquals('e', (string)$group->expressions()[1]->operand());
        $this->assertEquals('f', (string)$group->expressions()[1]->constraint()->parameters()[0]);
        $this->assertEquals('and', $group->operator());

        $group = $group->orWhere('g', 'h');
        // After this the complete old conditiongroup should be merged into a
        // new one, the new expression was added and the operator changes to or

        // After this the complete old conditiongroup should be merged into a
        // new one, the new expression was added and the operator changes to and

        $lastGroup = $group->expressions()[0];

        $this->assertEquals('and', $lastGroup->operator());

        $group1 = $lastGroup->expressions()[0];
        $this->assertEquals('a', (string)$group1->expressions()[0]->operand());
        $this->assertEquals('b', (string)$group1->expressions()[0]->constraint()->parameters()[0]);
        $this->assertEquals('or', $group1->operator());

        $this->assertEquals('c', (string)$group1->expressions()[1]->operand());
        $this->assertEquals('d', (string)$group1->expressions()[1]->constraint()->parameters()[0]);

        $condition = $lastGroup->expressions()[1];
        $this->assertEquals('e', (string)$condition->operand());
        $this->assertEquals('f', (string)$condition->constraint()->parameters()[0]);

        $condition = $group->expressions()[1];
        $this->assertEquals('g', (string)$condition->operand());
        $this->assertEquals('h', (string)$condition->constraint()->parameters()[0]);
        $this->assertEquals('or', $group->operator());


    }

    public function test_matches_finds_right_expressions()
    {
        $c = new Condition(new KeyExpression('email'), new Constraint('like', ['@gmail.com'], 'LIKE', 'operator'));

        $group = $this->newGroup()->where('login', 'dieter')
                                  ->where($c)
                                  ->orWhere(function (ConditionGroup $group) {
                                      return $group->where('name', 'LIKE', 'helmut')
                                                   ->where('age', '>', 6);
                                  });

        $group = Cheat::a($group);

        $this->assertCount(0, $group->findExpressions(['class' => Constraint::class, 'name' => 'equals']));
        $this->assertCount(1, $group->findExpressions(['class' => Constraint::class, 'operator' => '>']));
        $this->assertCount(1, $group->findExpressions(['class' => Condition::class,  'string' => 'login = dieter']));
    }

    public function test_allowConnectives_twice_throws_exception()
    {
        $this->expectException(BadMethodCallException::class);
        $group = $this->newGroup()->allowConnectives('or')->allowConnectives('and');
    }

    public function test_addWhere_with_unsupported_connective_throws_exception()
    {
        $this->expectException(UnSupported::class);
        $group = $this->newGroup()->allowConnectives('or');
        $this->assertEquals(['or'], $group->allowedConnectives());

        $this->assertCount(0, $group->conditions());

        $group = $group->where('login', 'dieter');

    }

    public function test_addWhere_with_unsupported_connective_added_later_throws_exception()
    {
        $this->expectException(UnSupported::class);
        $group = $this->newGroup()->allowConnectives('or');
        $this->assertEquals(['or'], $group->allowedConnectives());
        $this->assertFalse($group->areMultipleConnectivesAllowed());

        $this->assertCount(0, $group->conditions());

        $group = $group->orWhere('login', 'dieter');

        $this->assertCount(1, $group->conditions());

        $condition = $group->conditions()[0];

        $this->assertInstanceOf(KeyExpression::class, $condition->operand());
        $this->assertEquals('login', (string)$condition->operand());
        $this->assertEquals('dieter', $condition->constraint()->parameters()[0]);
        $this->assertEquals('=', $condition->constraint()->operator());
        $this->assertEquals('equal', $condition->constraint()->name());

        $group = $group->where('age', '>', 25);

    }

    public function test_addExpression_with_unsupported_connective_throws_exception()
    {
        $this->expectException(UnSupported::class);
        $group = $this->newGroup()->allowConnectives('and');
        $this->assertEquals(['and'], $group->allowedConnectives());

        $this->assertCount(0, $group->conditions());

        $group = $group->add($this->newGroup()->orWhere('login', 'dieter'));

    }

    public function test_addWhere_with_changing_connective_throws_excepion_if_forbidden()
    {
        $this->expectException(UnSupported::class);

        $group = $this->newGroup()->forbidMultipleConnectives();

        $this->assertFalse($group->areMultipleConnectivesAllowed());

        $this->assertCount(0, $group->conditions());

        $group = $group->where('login', 'dieter')
                       ->where('email', 'like', '%@%');

        $this->assertCount(2, $group->conditions());

        $condition = $group->conditions()[0];

        $group = $group->orWhere('age', '>', 25);

    }

    public function test_addExpression_with_changing_connective_throws_excepion_if_forbidden()
    {
        $this->expectException(UnSupported::class);

        $group = $this->newGroup()->forbidMultipleConnectives();

        $this->assertFalse($group->areMultipleConnectivesAllowed());

        $this->assertCount(0, $group->conditions());

        $group->add($this->newGroup([new Condition('login', new Constraint('=',['dieter']))]));

        $this->assertCount(1, $group->conditions());

        $group->add($this->newGroup([], 'or'));

    }

    public function test_addExpression_with_different_connective_in_subgroup_throws_excepion_if_forbidden()
    {
        $this->expectException(UnSupported::class);

        $group = $this->newGroup()->forbidMultipleConnectives();

        $this->assertFalse($group->areMultipleConnectivesAllowed());

        $this->assertCount(0, $group->conditions());

        $group->add($this->newGroup([new Condition('login', new Constraint('=',['dieter']))]));

        $this->assertCount(1, $group->conditions());

        $subGroup = $this->newGroup([]);

        $group->add($subGroup);

        $subGroup->orWhere('foo', 'bar');

    }

    public function test_addWhere_with_forbidden_nesting_throws_exception()
    {
        $this->expectException(UnSupported::class);

        $group = $this->newGroup()->forbidNesting();

        $this->assertFalse($group->isNestingAllowed());

        $this->assertCount(0, $group->conditions());

        $group->add($this->newGroup());

    }

    public function test_addWhere_with_forbidden_does_not_throw_exception_when_adding_conditions()
    {

        $group = $this->newGroup()->forbidNesting();

        $group->where('a', 'b')
              ->where('c', '<', 4)
              ->where('e', [1,2,5]);

    }

    public function test_allowOperators_twice_throws_exception()
    {
        $this->expectException(BadMethodCallException::class);
        $this->newGroup()->allowOperators('=')->allowOperators('=');
    }

    public function test_addWhere_with_unsuppored_operator_throws_exception()
    {
        $this->expectException(UnSupported::class);

        $group = $this->newGroup()->allowOperators('=');
        $this->assertEquals(['='], $group->allowedOperators());

        $group->where('login', '>', 5);

    }

    public function test_add_with_unsupported_operator_throws_exception()
    {
        $this->expectException(UnSupported::class);

        $group = $this->newGroup()->allowOperators('=');
        $this->assertEquals(['='], $group->allowedOperators());

        $group->add(new Condition('login', new Constraint('>', [5])));

    }

    public function test_add_with_supported_operator_throws_no_exception()
    {

        $group = $this->newGroup()->allowOperators('=');
        $this->assertEquals(['='], $group->allowedOperators());

        $group->add(new Condition('login', new Constraint('>', [5], '=')));

    }

    public function test_add_with_unsupported_operator_in_subGroup_throws_exception()
    {
        $this->expectException(UnSupported::class);

        $group = $this->newGroup()->allowOperators('=');
        $this->assertEquals(['='], $group->allowedOperators());

        $subGroup = $this->newGroup();
        $group->add($subGroup);

        $subGroup->add(new Condition('login', new Constraint('>', [5])));

    }

    public function test_add_with_supported_operator_in_subGroup_throws_no_exception()
    {

        $group = $this->newGroup()->allowOperators('=');
        $this->assertEquals(['='], $group->allowedOperators());

        $group->where('login', 'bill')->orWhere('login', 'jill');

    }

    public function test_add_with_unsupported_operator_in_subGroup_throws_exception_if_set_before_adding()
    {
        $this->expectException(UnSupported::class);

        $group = $this->newGroup()->allowOperators('=');
        $this->assertEquals(['='], $group->allowedOperators());

        $subGroup = $this->newGroup();
        $subGroup->add(new Condition('login', new Constraint('>', [5])));

        $group->add($subGroup);

    }

    public function test_allowMaxConditions_twice_throws_exception()
    {
        $this->expectException(BadMethodCallException::class);
        $this->newGroup()->allowMaxConditions(3)->allowMaxConditions(3);
    }

    public function test_addWhere_with_more_than_max_conditions_throws_exception()
    {
        $this->expectException(OutOfBoundsException::class);
        $group = $this->newGroup()->allowMaxConditions(3);
        $this->assertEquals(3, $group->maxConditions());
        $group->where('a', 'b')
              ->where('c', 'd')
              ->where('e', 'f')
              ->where('g', 'h');
    }

    public function test_addWhere_with_less_than_max_conditions_throws_no_exception()
    {
        $group = $this->newGroup()->allowMaxConditions(3);
        $this->assertEquals(3, $group->maxConditions());
        $group->where('a', 'b')
              ->where('c', 'd')
              ->where('e', 'f');
    }

    public function test_add_with_more_than_max_conditions_throws_exception()
    {
        $this->expectException(OutOfBoundsException::class);
        $group = $this->newGroup()->allowMaxConditions(3);
        $this->assertEquals(3, $group->maxConditions());
        $group->add($this->cond('a', '=', 'b'))
              ->add($this->cond('c', '=', 'd'))
              ->add($this->cond('e', '=', 'f'))
              ->add($this->cond('g', '=', 'h'));
    }

    public function test_add_with_more_than_max_conditions_throws_exception_if_restricted_after_adding()
    {
        $this->expectException(OutOfBoundsException::class);
        $group = $this->newGroup();
        $this->assertEquals(0, $group->maxConditions());
        $group->add($this->cond('a', '=', 'b'))
              ->add($this->cond('c', '=', 'd'))
              ->add($this->cond('e', '=', 'f'))
              ->add($this->cond('g', '=', 'h'));
        $group->allowMaxConditions(3);
        $this->assertEquals(3, $group->maxConditions());
    }

    protected function newGroup($conditions=[], $operator='and')
    {
        return new ConditionGroup($conditions, $operator);
    }

    protected function cond($key, $operator, $parameters)
    {
        return new Condition($key, new Constraint($operator, (array)$parameters, $operator));
    }
}

class ConditionGroupTest_ConditionGroup
{
    use LogicalGroupTrait;
    use ConditionalTrait;
}
