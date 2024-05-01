<?php


namespace Ems\Expression;

use Ems\Contracts\Expression\ConstraintGroup as ConstraintGroupContract;
use Ems\Core\Exceptions\KeyNotFoundException;
use Ems\Core\Expression;
use InvalidArgumentException;

/**
 * @group validation
 **/
class ConstraintGroupTest extends \Ems\TestCase
{
    public function test_implements_interface()
    {
        $this->assertInstanceOf(ConstraintGroupContract::class, $this->newGroup());
    }

    public function test_operator()
    {
        $rules = ['required','exists:users.id','unique'];
        $definition = $this->newGroup($rules);
        $this->assertEquals('and', $definition->operator());
        $this->assertSame($definition, $definition->setOperator('or'));
        $this->assertEquals('or', $definition->operator());
    }

    public function test_setOperator_throws_exception_without_unknown_operator()
    {
        $this->expectException(InvalidArgumentException::class);
        $rules = ['required','exists:users.id','unique'];
        $definition = $this->newGroup($rules);
        $definition->setOperator('foo');
    }

    public function test_fill_fills_by_array()
    {
        $rules = ['required','exists:users.id','unique'];
        $definition = $this->newGroup($rules);
        $this->assertCount(3, $definition->constraints());
        $this->assertCount(3, $definition->expressions());
        $this->assertNull($definition->required);
    }

    public function test_fill_fills_by_string()
    {
        $rules = 'required|exists:users.id|unique|in:one,two,three';
        $definition = $this->newGroup($rules);
        $this->assertCount(4, $definition->constraints());
        $this->assertNull($definition->required);
        $this->assertEquals('users.id', $definition->exists);
        $this->assertEquals(['one', 'two', 'three'], $definition->in);
    }

    public function test_get_returns_only_when_multiple_values_are_set()
    {
        $rules = 'required|exists:users.id|unique|in:one,two,three';
        $definition = $this->newGroup($rules);
        $this->assertNull($definition->required);
        $this->assertNull($definition->unique);
        $this->assertEquals('users.id', $definition->exists);
        $this->assertEquals(['one', 'two', 'three'], $definition->in);
    }

    public function test_get_returns_only_when_multiple_values_are_set_in_expression_syntax()
    {
        $definition = $this->newGroup();

        $definition->add(new Constraint('required'));
        $definition->add(new Constraint('exists', ['users.id']));
        $definition->add(new Constraint('unique'));
        $definition->add(new Constraint('in', ['one', 'two', 'three']));

        $this->assertNull($definition->required);
        $this->assertNull($definition->unique);
        $this->assertEquals('users.id', $definition->exists);
        $this->assertEquals(['one', 'two', 'three'], $definition->in);
    }

    public function test_set_replaces_parameters_if_array_is_passed()
    {
        $rules = 'required|exists:users.id|unique|in:one,two,three';
        $definition = $this->newGroup($rules);
        $this->assertEquals('users.id', $definition->exists);
        $definition->exists = [5,6,7];
        $this->assertEquals([5,6,7], $definition->exists);
    }

    public function test_set_replaces_parameters_if_none_are_setted()
    {
        $rules = 'required|exists:users.id|unique|in:one,two,three';
        $definition = $this->newGroup($rules);

        $this->assertNull($definition->required);
        $definition->required = true;
        $this->assertSame(true, $definition->required);
    }

    public function test_set_replaces_parameters_if_one_is_setted()
    {
        $rules = 'required|exists:users.id|unique|in:one,two,three';
        $definition = $this->newGroup($rules);

        $this->assertEquals('users.id', $definition->exists);
        $definition->exists = 'addresses.id';
        $this->assertSame('addresses.id', $definition->exists);
    }

    public function test_set_replaces_all_parameters_if_multiple_were_setted()
    {
        $rules = 'required|exists:users.id|unique|in:one,two,three';
        $definition = $this->newGroup($rules);

        $this->assertEquals(['one', 'two', 'three'], $definition->in);
        $this->assertEquals(['one', 'two', 'three'], $definition['in']->parameters());
        $definition->in = 'four';
        $this->assertSame('four', $definition->in);
    }

    public function test_offsetSet_replaces_constraint()
    {
        $rules = 'required|exists:users.id|unique|in:one,two,three';
        $definition = $this->newGroup($rules);
        $this->assertEquals(['users.id'], $definition['exists']->parameters());
        $definition['exists'] = new Constraint('exists', ['users.parent_id']);
        $this->assertEquals(['users.parent_id'], $definition['exists']->parameters());
    }

    public function test_offsetSet_throws_exception_if_offset_not_name()
    {
        $this->expectException(InvalidArgumentException::class);
        $definition = $this->newGroup();
        $definition['exists'] = new Constraint('different', ['users.parent_id']);
    }

    public function test_offsetSet_throws_exception_if_expression_is_no_constraint()
    {
        $this->expectException(InvalidArgumentException::class);
        $definition = $this->newGroup();
        $definition['exists'] = new Expression('exists');
    }

    public function test_unset_constraint()
    {
        $rules = 'required|exists:users.id|unique|in:one,two,three';
        $definition = $this->newGroup($rules);

        $this->assertCount(4, $definition->constraints());
        $this->assertTrue(isset($definition->in));
        $this->assertTrue(isset($definition['in']));
        unset($definition->in);
        $this->assertFalse(isset($definition->in));
        $this->assertFalse(isset($definition['in']));
        $this->assertCount(3, $definition->constraints());
    }

    public function test_unset_constraint_with_ArrayAccess()
    {
        $rules = 'required|exists:users.id|unique|in:one,two,three';
        $definition = $this->newGroup($rules);

        $this->assertCount(4, $definition->constraints());
        $this->assertTrue(isset($definition['in']));
        unset($definition['in']);
        $this->assertFalse(isset($definition['in']));
        $this->assertCount(3, $definition->constraints());
    }

    public function test_unset_constraint_in_expression_syntax()
    {
        $rules = 'required|exists:users.id|unique|in:one,two,three';
        $definition = $this->newGroup($rules);

        $this->assertCount(4, $definition->constraints());
        $this->assertTrue(isset($definition->in));
        $definition->remove(new Constraint('in', ['one', 'two', 'three']));
        $this->assertFalse(isset($definition->in));
        $this->assertCount(3, $definition->constraints());
    }

    public function test_iterate_over_definition()
    {
        $rules = 'required|exists:users.id|unique|in:one,two,three';
        $definition = $this->newGroup($rules);

        $awaited = [
            'required' => [],
            'exists'   => ['users.id'],
            'unique'   => [],
            'in'       => ['one', 'two', 'three']
        ];

        $array = [];
        foreach ($definition->constraints() as $name=>$constraint) {
            $array[$name] = $constraint->parameters();
        }

        $this->assertEquals($awaited, $array);
    }

    public function test_string_representation_matches_definition()
    {

        $awaited = 'required() AND exists(users.id) AND unique() AND IN (one, two, three) AND = Acme AND >= 18';

        $definition = $this->newGroup();
        $definition->add(new Constraint('required'));
        $definition->add(new Constraint('exists', ['users.id']));
        $definition->add(new Constraint('unique'));
        $definition->add(new Constraint('in', [['one', 'two', 'three']], 'IN'));
        $definition->add(new Constraint('equals', ['Acme'], '='));
        $definition->add(new Constraint('min', [18], '>='));

        $this->assertEquals($awaited, "$definition");
    }

    public function test_get_throws_exception_if_key_not_found()
    {
        $this->expectException(KeyNotFoundException::class);
        return $this->newGroup()->hello;
    }

    protected function newGroup($definition=null)
    {
        return new ConstraintGroup($definition);
    }
}
