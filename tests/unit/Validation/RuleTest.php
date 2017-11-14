<?php


namespace Ems\Validation;

use Ems\Contracts\Validation\Rule as RuleContract;
use Ems\Contracts\Validation\Validator as ValidatorContract;

/**
 * @group validation
 **/
class RuleTest extends \Ems\TestCase
{
    public function test_implements_interface()
    {
        $this->assertInstanceOf(RuleContract::class, $this->newRule());
    }

    public function test_fill_fills_by_array()
    {
        $rules = ['required','exists:users.id','unique'];
        $definition = $this->newRule($rules);
        $this->assertCount(3, $definition);
        $this->assertNull($definition->required);
    }

    public function test_fill_fills_by_string()
    {
        $rules = 'required|exists:users.id|unique|in:one,two,three';
        $definition = $this->newRule($rules);
        $this->assertCount(4, $definition);
        $this->assertNull($definition->required);
        $this->assertEquals('users.id', $definition->exists);
        $this->assertEquals(['one', 'two', 'three'], $definition->in);
    }

    public function test_get_returns_only_when_multiple_values_are_set()
    {
        $rules = 'required|exists:users.id|unique|in:one,two,three';
        $definition = $this->newRule($rules);
        $this->assertNull($definition->required);
        $this->assertNull($definition->unique);
        $this->assertEquals('users.id', $definition->exists);
        $this->assertEquals(['one', 'two', 'three'], $definition->in);
    }

    public function test_set_replaces_parameters_if_array_is_passed()
    {
        $rules = 'required|exists:users.id|unique|in:one,two,three';
        $definition = $this->newRule($rules);
        $this->assertEquals('users.id', $definition->exists);
        $definition->exists = [5,6,7];
        $this->assertEquals([5,6,7], $definition->exists);
    }

    public function test_set_replaces_parameters_if_none_are_setted()
    {
        $rules = 'required|exists:users.id|unique|in:one,two,three';
        $definition = $this->newRule($rules);

        $this->assertNull($definition->required);
        $definition->required = true;
        $this->assertSame(true, $definition->required);
    }

    public function test_set_replaces_parameters_if_one_is_setted()
    {
        $rules = 'required|exists:users.id|unique|in:one,two,three';
        $definition = $this->newRule($rules);

        $this->assertEquals('users.id', $definition->exists);
        $definition->exists = 'addresses.id';
        $this->assertSame('addresses.id', $definition->exists);
    }

    public function test_set_replaces_all_parameters_if_multiple_were_setted()
    {
        $rules = 'required|exists:users.id|unique|in:one,two,three';
        $definition = $this->newRule($rules);

        $this->assertEquals(['one', 'two', 'three'], $definition->in);
        $definition->in = 'four';
        $this->assertSame('four', $definition->in);
    }

    public function test_unset_constraint()
    {
        $rules = 'required|exists:users.id|unique|in:one,two,three';
        $definition = $this->newRule($rules);

        $this->assertCount(4, $definition);
        $this->assertTrue(isset($definition->in));
        unset($definition->in);
        $this->assertFalse(isset($definition->in));
        $this->assertCount(3, $definition);
    }

    public function test_iterate_over_definition()
    {
        $rules = 'required|exists:users.id|unique|in:one,two,three';
        $definition = $this->newRule($rules);

        $awaited = [
            'required' => [],
            'exists'   => ['users.id'],
            'unique'   => [],
            'in'       => ['one', 'two', 'three']
        ];

        $array = [];
        foreach ($definition as $name=>$parameters) {
            $array[$name] = $parameters;
        }

        $this->assertEquals($awaited, $array);
    }

    public function test_string_representation_matches_definition()
    {
        $rules = 'required|exists:users.id|unique|in:one,two,three';
        $definition = $this->newRule($rules);

        $this->assertEquals($rules, "$definition");
    }

    public function test_setOperator_returns_instance()
    {
        $rules = 'required|exists:users.id|unique|in:one,two,three';
        $definition = $this->newRule($rules);

        $this->assertSame($definition, $definition->setOperator('and'));
    }

    /**
     * @expectedException InvalidArgumentException
     **/
    public function test_setOperator_with_not_and_throws_exception()
    {
        $rules = 'required|exists:users.id|unique|in:one,two,three';
        $definition = $this->newRule($rules);

        $this->assertSame($definition, $definition->setOperator('or'));
    }

    /**
     * @expectedException Ems\Core\Exceptions\KeyNotFoundException
     **/
    public function test_get_throws_exception_if_key_not_found()
    {
        return $this->newRule()->hello;
    }

    protected function newRule($definition=null)
    {
        return new Rule($definition);
    }
}
