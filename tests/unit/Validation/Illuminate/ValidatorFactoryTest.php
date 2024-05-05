<?php


namespace Ems\Validation\Illuminate;

use Ems\TestCase;
use Ems\Validation\Validator;

/**
 * @group validation
 **/
class ValidatorFactoryTest extends TestCase
{
    public function test_validator_returns_validator_with_passed_rules()
    {
        $factory = $this->newFactory();

        $rules = ['login' => 'required|min:2|max:255'];
        $validator = $this->mock(Validator::class);
        $validator->shouldReceive('canMergeRules')
                  ->once()
                  ->andReturn(false);

        $factory->createObjectsBy(function ($class) use ($rules, $validator) {
            if ($class == Validator::class) {
                return $validator;
            }
            if ($class == IlluminateBaseValidator::class) {
                return $this->mock(IlluminateBaseValidator::class);
            }
        });

        $this->assertSame($validator, $factory->validator($rules));

    }

    protected function newFactory()
    {
        return new ValidatorFactory();
    }

}

