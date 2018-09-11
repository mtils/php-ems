<?php


namespace Ems\Validation;

use Ems\Contracts\Core\AppliesToResource;
use Ems\Core\NamedObject;
use Ems\Core\FakeEntity as GenericEntity;
use Ems\Contracts\Validation\Validation;
use Ems\Contracts\Validation\GenericValidator as GenericValidatorContract;
use Ems\Contracts\Validation\AlterableValidator as AlterableValidatorContract;
use Ems\Contracts\Validation\ResourceRuleDetector;
use Ems\Testing\LoggingCallable;

/**
 * @group validation
 **/
class GenericValidatorTest extends \Ems\TestCase
{
    public function test_implements_interface()
    {
        $this->assertInstanceOf(GenericValidatorContract::class, $this->newValidator());
        $this->assertInstanceOf(AlterableValidatorContract::class, $this->newValidator());
    }

    public function test_validate_forwards_to_passed_callable()
    {

        $handler = new LoggingCallable(function (Validation $validation, array $input, array $rules, AppliesToResource $resource, $locale) {
            return 'not true';
        });

        $rules = ['password' => 'required'];
        $parsed = ['password' => ['required' => []]];
        $input = ['password' => 'blabla'];
        $resource = new NamedObject(15, 'king', 'category');
        $locale = 'cz';

        $validator = $this->newValidator($rules, $handler);

        $this->assertTrue($validator->validate($input, $resource, $locale));
        $this->assertInstanceOf(Validation::class, $handler->arg(0));
        $this->assertEquals($input, $handler->arg(1));
        $this->assertEquals($parsed, $handler->arg(2));
        $this->assertSame($resource, $handler->arg(3));
        $this->assertEquals($locale, $handler->arg(4));
    }

    public function test_validate_throws_validation_exception_validation_has_count()
    {

        $handler = new LoggingCallable(function (Validation $validation, array $input, array $rules, AppliesToResource $resource, $locale) {
            $validation->addFailure('password', 'between', [1,24]);
            return 'not true';
        });

        $rules = ['password' => 'required'];
        $parsed = ['password' => ['required' => []]];
        $input = ['password' => 'blabla'];

        $failures = [
            'password' => [
                'between' => [1,24]
            ]
        ];

        $resource = new NamedObject(15, 'king', 'category');
        $locale = 'cz';

        $validator = $this->newValidator($rules, $handler);

        try {

            $validator->validate($input, $resource, $locale);
            $this->fail("Validator had to throw an validation exception");

        } catch (Validation $validation) {
            $this->assertEquals($failures, $validation->failures());
        }

        $this->assertInstanceOf(Validation::class, $handler->arg(0));
        $this->assertEquals($input, $handler->arg(1));
        $this->assertEquals($parsed, $handler->arg(2));
        $this->assertSame($resource, $handler->arg(3));
        $this->assertEquals($locale, $handler->arg(4));
    }

    /**
     * @expectedException Ems\Contracts\Core\Errors\ConfigurationError
     **/
    public function test_validate_throws_validation_if_no_callable_assigned()
    {

        $validator = $this->newValidator(['password' => 'min:3']);

        $validator->validate([]);

    }

    /**
     * @expectedException Ems\Core\Exceptions\UnConfiguredException
     **/
    public function test_buildValidator_throws_exception_if_no_resource_assigned_and_resourceName_is_called()
    {
        $validator = $this->newValidator();
        $detector = $this->mock(XTypeProviderValidatorFactory::class);

        $validator->resourceName();

    }

    public function test_rules_returns_parsedRules()
    {

        $rules = [
            'login'     => 'required|min:5|max:64',
            'email'     => 'required|email',
            'signature' => 'min:3|max:255',
            'type_id'   => 'in:1,2,3|in-between:1,3'
        ];

        $validator = $this->newValidator($rules);

        $parsed = [
            'login' => [
                'required' => [],
                'min'      => [5],
                'max'      => [64]
            ],
            'email' => [
                'required' => [],
                'email'    => []
            ],
            'signature' => [
                'min'      => [3],
                'max'      => [255]
            ],
            'type_id'   => [
                'in'       => [1,2,3],
                'in_between' => [1,3]
            ]
        ];

        $this->assertSame($validator, $validator->setRules($rules));

        $this->assertEquals($parsed, $validator->rules());


    }

    public function test_detectRules_if_no_rules_setted()
    {
        $validator = $this->newValidator([], function () { return true; } );
        $detector = $this->mock(ResourceRuleDetector::class);

        $resource = new NamedObject;

        $rules = [
            'login'     => 'required|min:5|max:64',
            'email'     => 'required|email',
            'signature' => 'min:3|max:255',
            'type_id'   => 'in:1,2,3|in-between:1,3'
        ];

        $parsed = [
            'login' => [
                'required' => [],
                'min'      => [5],
                'max'      => [64]
            ],
            'email' => [
                'required' => [],
                'email'    => []
            ],
            'signature' => [
                'min'      => [3],
                'max'      => [255]
            ],
            'type_id'   => [
                'in'       => [1,2,3],
                'in_between' => [1,3]
            ]
        ];

        $input = [
            'login'     => 'mtils',
            'email'     => 'mtils@foo.org',
            'signature' => 'Bye',
            'type_id'   => 2
        ];

        $detector->shouldReceive('detectRules')
                 ->with($resource, [])
                 ->once()
                 ->andReturn($rules);

        $validator->injectRuleDetector($detector);

        $this->assertTrue($validator->validate($input, $resource));
        $this->assertEquals($parsed, $validator->rules());

    }

    /**
     * @expectedException Ems\Core\Exceptions\UnConfiguredException
     **/
    public function test_detectRules_throws_exception_if_no_detector_assigned()
    {
        $validator = $this->newValidator([], function () { return true; });
        $validator->setResource(new NamedObject());
        $validator->validate([]);

    }

    public function test_merge_merges_rules()
    {

        $handler = new LoggingCallable(function (Validation $validation, array $input, array $rules, AppliesToResource $resource, $locale) {
            return 'not true';
        });

        $rules = ['password' => 'required'];

        $input = ['password' => 'blabla'];

        $resource = new NamedObject(15, 'king', 'category');

        $locale = 'cz';

        $parsed = [
            'password' => [
                'min'      => [3]
            ],
            'login'    => [
                'max'      => [64]
            ]
        ];

        $validator = $this->newValidator($rules, $handler);
        $validator->mergeRules(['password' => 'min:3', 'login' => 'max:64']);

        $this->assertTrue($validator->validate($input, $resource, $locale));
        $this->assertInstanceOf(Validation::class, $handler->arg(0));
        $this->assertEquals($input, $handler->arg(1));
        $this->assertEquals($parsed, $handler->arg(2));
        $this->assertSame($resource, $handler->arg(3));
        $this->assertEquals($locale, $handler->arg(4));
    }

    public function test_get_and_set_resourceName()
    {

        $resource = new NamedObject(14, 'king', 'users');
        $validator = $this->newValidator(['password' => 'min:3']);
        $this->assertSame($validator, $validator->setResource($resource));
        $this->assertEquals('users', $validator->resourceName());
        $this->assertSame($resource, $validator->resource());

    }

    public function test_rules_fires_hooks_once_when_parsing_rules()
    {

        $validator = $this->newValidator();

        $before = new LoggingCallable;
        $after = new LoggingCallable;

        $validator->onBefore('parseRules', $before);
        $validator->onAfter('parseRules', $after);

        $rules = [
            'login'     => 'required|min:5|max:64',
            'email'     => 'required|email',
            'signature' => 'min:3|max:255',
            'type_id'   => 'in:1,2,3|in-between:1,3'
        ];

        $parsed = [
            'login' => [
                'required' => [],
                'min'      => [5],
                'max'      => [64]
            ],
            'email' => [
                'required' => [],
                'email'    => []
            ],
            'signature' => [
                'min'      => [3],
                'max'      => [255]
            ],
            'type_id'   => [
                'in'       => [1,2,3],
                'in_between' => [1,3]
            ]
        ];

        $validator->setRules($rules);

        $this->assertEquals($parsed, $validator->rules());

        // before receives the unparsed rules
        $this->assertEquals($rules, $before->arg(0));

        // after receives the parsed rules
        $this->assertEquals($parsed, $after->arg(0));

        // Trigger it once again to test the ONCE
        $this->assertEquals($parsed, $validator->rules());

        $this->assertCount(1, $before);
        $this->assertCount(1, $after);

    }

    public function test_prepareRulesForValidation_removes_required_if_resource_exists()
    {

        $rules = [
            'name'      => 'min:2|max:255',
            'iso_code'  => 'min:2|max:2',
            'password'  => 'required'
        ];

        $input = [
            'name'      => 'France',
            'iso_code'  => 'fr'
        ];

        $country = (new GenericEntity)->makeNew(false);

        $breaker = new LoggingCallable(function () {
            throw new BreakException;
        });

        $awaitedRules = [
            'name' => [
                'min' => [2],
                'max' => [255]
            ],
            'iso_code' => [
                'min' => [2],
                'max' => [2]
            ]
        ];

        try {

            $validator = $this->newValidator();
            $validator->setRules($rules);

            $validator->onBefore('validate', $breaker);

            $validator->validate($input, $country);

            $this->fail('The injected exception throw was not performed');

        } catch (BreakException $e) {
            $this->assertEquals($awaitedRules, $breaker->arg(1));
        }
    }

    public function test_prepareRulesForValidation_removes_nested_array_rules_if_not_present_in_input()
    {

        $rules = [
            'name'              => 'min:2|max:255',
            'iso_code'          => 'min:2|max:2',
            'password'          => 'required',
            'address.country'   => 'min:2|max:255',
            'address.street'    => 'min:2|max:255',
            'address.location.name' => 'min:2|max:255',
            'category.id'       => 'min:2|max:255',
            'profile.nickname'  => 'min:2|max:255'
        ];

        $input = [
            'name'      => 'France',
            'iso_code'  => 'fr',
            'profile'   => [
                'nickname' => 'bla'
            ]
        ];

        $country = (new GenericEntity)->makeNew(false);

        $breaker = new LoggingCallable(function () {
            throw new BreakException;
        });

        $awaitedRules = [
            'name' => [
                'min' => [2],
                'max' => [255]
            ],
            'iso_code' => [
                'min' => [2],
                'max' => [2]
            ],
//             'category.id' => [
//                 'min' => [2],
//                 'max' => [255]
//             ],
            'profile.nickname' => [
                'min' => [2],
                'max' => [255]
            ]
        ];

        try {

            $validator = $this->newValidator();
            $validator->setRules($rules);

            $validator->onBefore('validate', $breaker);

            $validator->validate($input, $country);

            $this->fail('The injected exception throw was not performed');

        } catch (BreakException $e) {
//             print_r($breaker->arg(1));
            $this->assertEquals($awaitedRules, $breaker->arg(1));
        }
    }

    public function test_prepareRulesForValidation_removes_nested_array_rules_if_not_present_in_input_when_optional()
    {

        $rules = [
            'name'              => 'min:2|max:255',
            'iso_code'          => 'min:2|max:2',
            'password'          => 'required',
            'address.country'   => 'min:2|max:255',
            'address.street'    => 'min:2|max:255',
            'address.location.name' => 'min:2|max:255',
            'category.id'       => 'min:2|max:255',
            'profile.nickname'  => 'min:2|max:255'
        ];

        $input = [
            'name'      => 'France',
            'iso_code'  => 'fr',
            'address' => [
                'street' => 'Elm Str.'
            ],
            'profile'   => [
                'nickname' => 'bla'
            ]
        ];

        $country = (new GenericEntity)->makeNew(true);

        $breaker = new LoggingCallable(function () {
            throw new BreakException;
        });

        $awaitedRules = [
            'name' => [
                'min' => [2],
                'max' => [255]
            ],
            'iso_code' => [
                'min' => [2],
                'max' => [2]
            ],
            'password' => [
                'required' => []
            ],
            'address.country' => [
                'min' => [2],
                'max' => [255]
            ],
            'address.street' => [
                'min' => [2],
                'max' => [255]
            ],
            'address.location.name' => [
                'min' => [2],
                'max' => [255]
            ],
//             'category.id' =>[
//                 'min' => [2],
//                 'max' => [255]
//             ],
            'profile.nickname' => [
                'min' => [2],
                'max' => [255]
            ]
        ];

        try {

            $validator = new ValidatorWithOptionalRelations;
            $validator->setRules($rules);

            $validator->onBefore('validate', $breaker);

            $validator->validate($input, $country);

            $this->fail('The injected exception throw was not performed');

        } catch (BreakException $e) {
//             print_r($breaker->arg(1));
            $this->assertEquals($awaitedRules, $breaker->arg(1));
        }
    }

    public function test_validateForbidden()
    {

        $handler = new LoggingCallable(function (Validation $validation, array $input, array $rules, AppliesToResource $resource, $locale) {
            return true;
        });

        $rules = ['created_at' => 'forbidden'];
        $input = ['password' => 'blabla'];
        $resource = new NamedObject(15, 'king', 'category');
        $locale = 'cz';

        $validator = $this->newValidator($rules, $handler);

        $this->assertTrue($validator->validate($input, $resource, $locale));

        try {
            $validator->validate(['created_at'=>new \DateTime], $resource, $locale);
            $this->fail('Validation should fail with forbidden attributes');
        } catch (Validation $e) {

            $this->assertEquals([
                'created_at' => ['forbidden'=>[]]
            ],
            $e->failures());

        }
    }


    protected function newValidator($rules=[], callable $validator=null)
    {
        return new GenericValidator($rules, $validator);
    }
}

class BreakException extends \Exception
{
}

class ValidatorWithOptionalRelations extends GenericValidator
{
    protected function isOptionalRelation($relation)
    {
        return $relation == 'category';
    }
}
