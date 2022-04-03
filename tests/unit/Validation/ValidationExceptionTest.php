<?php


namespace Ems\Validation;

use Ems\Contracts\Validation\Validation;
use Ems\Contracts\Validation\Validator as ValidatorContract;
use Ems\Contracts\Validation\ValidationException;

/**
 * @group validation
 **/
class ValidationExceptionTest extends \Ems\TestCase
{
    public function test_implements_interface()
    {
        $this->assertInstanceOf(Validation::class, $this->newValidation());
    }

    public function test_addFailure_returns_same_instance()
    {
        $validation = $this->newValidation();
        $this->assertSame($validation, $validation->addFailure('login', 'required'));
    }

    public function test_failures_returns_added_failures()
    {
        $validation = $this->newValidation();

        $awaited = [
            'login' => [
                'required' => [],
                'min'      => [5],
                'max'      => [64]
            ],
            'password' => [
                'min' => [8]
            ]
        ];

        foreach ($awaited as $key=>$rules) {
            foreach ($rules as $ruleName=>$parameters) {
                $validation->addFailure($key, $ruleName, $parameters);
            }
        }

        $this->assertEquals($awaited, $validation->failures());

    }

    public function test_parameters_returns_rule_parameters()
    {
        $awaited = [
            'login' => [
                'required' => [],
                'min'      => [5],
                'max'      => [64]
            ],
            'password' => [
                'min' => [8]
            ]
        ];

        $validation = $this->newValidation($awaited);

        $this->assertEquals($awaited['login']['required'], $validation->parameters('login', 'required'));
        $this->assertEquals($awaited['login']['min'], $validation->parameters('login', 'min'));
        $this->assertEquals($awaited['login']['max'], $validation->parameters('login', 'max'));
        $this->assertEquals($awaited['password']['min'], $validation->parameters('password', 'min'));

    }

    /**
     * @expectedException \Ems\Contracts\Core\Errors\NotFound
     **/
    public function test_parameters_does_not_fail_on_missing_key()
    {
        $validation = $this->newValidation();

        $validation->addFailure('age', 'numeric');
        $validation->addFailure('age', 'min', [6]);

        $this->assertEquals([], $validation->parameters('foo', 'max'));
    }

    /**
     * @expectedException \Ems\Contracts\Core\Errors\NotFound
     **/
    public function test_parameters_does_not_fail_on_missing_rules()
    {
        $validation = $this->newValidation();

        $validation->addFailure('age', 'numeric');
        $validation->addFailure('age', 'min', [6]);

        $this->assertEquals([], $validation->parameters('age', 'max'));
    }

    public function test_validatorClass_and_setValidatorClass()
    {
        $validation = $this->newValidation();
        $validator = $this->mock(ValidatorContract::class);
        $this->assertSame($validation, $validation->setValidatorClass('foo'));
        $this->assertEquals('foo', $validation->validatorClass());
    }

    public function test_jsonSerialize_returns_array_to_create_new_validation()
    {

        $rules = [
            'login'     => 'required|min:5|max:64',
            'password'  => 'min:8'
        ];

        $failures = [
            'login' => [
                'required' => [],
                'min'      => [5],
                'max'      => [64]
            ],
            'password' => [
                'min' => [8]
            ]
        ];

        $validation = $this->newValidation($failures, $rules, 'foo');

        $json = json_encode($validation);

        $validation2 = $this->newValidation();
        $this->assertSame($validation2, $validation2->fill(json_decode($json, true)));

        $this->assertEquals($validation->failures(), $validation2->failures());
        $this->assertEquals($validation->rules(), $validation2->rules());
        $this->assertEquals($validation->validatorClass(), $validation2->validatorClass());

    }

    public function test_offsetExists_works()
    {
        $awaited = [
            'login' => [
                'required' => [],
                'min'      => [5],
                'max'      => [64]
            ],
            'password' => [
                'min' => [8]
            ]
        ];

        $validation = $this->newValidation($awaited);

        $this->assertTrue(isset($validation['login']));
        $this->assertFalse(isset($validation['foo']));

    }

    public function test_offsetGet_works()
    {
        $awaited = [
            'login' => [
                'required' => [],
                'min'      => [5],
                'max'      => [64]
            ],
            'password' => [
                'min' => [8]
            ]
        ];

        $validation = $this->newValidation($awaited);

        $this->assertEquals($awaited['login'], $validation['login']);

    }

    public function test_offsetUnset_works()
    {
        $awaited = [
            'login' => [
                'required' => [],
                'min'      => [5],
                'max'      => [64]
            ],
            'password' => [
                'min' => [8]
            ]
        ];

        $validation = $this->newValidation($awaited);

        $this->assertTrue(isset($validation['login']));
        unset($validation['login']);
        $this->assertFalse(isset($validation['login']));

    }

    /**
     * @expectedException \Ems\Contracts\Core\Errors\Unsupported
     **/
    public function test_offsetSet_throws_Unsupported()
    {
        $validation = $this->newValidation();
        $validation['foo'] = [];
    }

    public function test_iterating_by_foreach()
    {
        $awaited = [
            'login' => [
                'required' => [],
                'min'      => [5],
                'max'      => [64]
            ],
            'password' => [
                'min' => [8]
            ]
        ];

        $validation = $this->newValidation($awaited);

        $copy = [];

        foreach ($validation as $key=>$rules) {

            foreach ($rules as $ruleName=>$parameters) {

                if (!isset($copy[$key])) {
                    $copy[$key] = [];
                }

                $copy[$key][$ruleName] = $parameters;

            }
        }

        $this->assertEquals($copy, $awaited);
    }

    public function test_count_returns_failure_count()
    {
        $awaited = [
            'login' => [
                'required' => [],
                'min'      => [5],
                'max'      => [64]
            ],
            'password' => [
                'min' => [8]
            ]
        ];

        $validation = $this->newValidation($awaited);

        $this->assertCount(4, $validation);
    }

    protected function newValidation(array $failures=[], array $rules=[], $validatorClass=null)
    {
        return new ValidationException($failures, $rules, $validatorClass);
    }
}
