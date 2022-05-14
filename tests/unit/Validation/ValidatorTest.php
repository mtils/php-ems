<?php
/**
 *  * Created by mtils on 27.11.2021 at 20:40.
 **/

namespace Ems\Validation;

use DateTime;
use Ems\Contracts\Core\AppliesToResource;
use Ems\Contracts\Validation\Validation;
use Ems\Contracts\Validation\ValidationException;
use Ems\Contracts\Validation\Validator as ValidatorContract;
use Ems\Core\FakeEntity as GenericEntity;
use Ems\Core\NamedObject;
use Ems\TestCase;
use Ems\Testing\LoggingCallable;

use function implode;
use function var_export;

class ValidatorTest extends TestCase
{
    /**
     * @test
     */
    public function it_implements_interface()
    {
        $this->assertInstanceOf(ValidatorContract::class, $this->make());
    }

    /**
     * @test
     */
    public function it_validates_simple_input()
    {
        $rules = [
            'login'     => 'required|min:5|max:64',
            'email'     => 'required|email',
            'signature' => 'min:3|max:255',
            'type_id'   => 'in:1,2,3'
        ];

        $input = [
            'login'     => 'mtils',
            'email'     => 'mtils@foo.org',
            'signature' => 'Bye',
            'type_id'   => 2
        ];

        $this->assertPasses($input, $rules);
    }

    /**
     * @test
     */
    public function it_validates_dates_without_format()
    {
        $rules = [
            'birthday'  => 'date'
        ];

        $input = [
            'birthday'     => '1976-05-31'
        ];

        $this->assertPasses($input, $rules);

        $input = [
            'birthday'     => '05/31/1976'
        ];

        $this->assertPasses($input, $rules);

        $input = [
            'birthday'     => 'now'
        ];

        $this->assertFailsWith($input, $rules, 'date');

    }

    /**
     * @test
     */
    public function it_validates_dates_with_format()
    {
        $rules = [
            'birthday'  => 'date'
        ];

        $input = [
            'birthday'     => '31 1976 05'
        ];

        $this->assertFailsWith($input, $rules, 'date');

        $this->assertPasses($input, $rules, null, [ValidatorContract::DATE_FORMAT=>'d Y m']);

        $rules = [
            'birthday'  => 'date:d Y m'
        ];

        $this->assertPasses($input, $rules);

    }

    /**
     * @test
     */
    public function it_validates_datetime_without_format()
    {
        $rules = [
            'created'  => 'datetime'
        ];

        $input = [
            'created'     => '1976-05-31 12:53'
        ];

        $this->assertPasses($input, $rules);

        $input = [
            'created'     => '1976-05-31'
        ];

        $this->assertFailsWith($input, $rules, 'datetime');

        $input = [
            'created'     => 'now'
        ];

        $this->assertFailsWith($input, $rules, 'datetime');

    }

    /**
     * @test
     */
    public function it_validates_datetime_with_format()
    {
        $rules = [
            'created'  => 'datetime:m Y d H;i'
        ];

        $input = [
            'created'     => '05 1976 31 12;53'
        ];

        $this->assertPasses($input, $rules);

        $input = [
            'created'     => '05 1976/31 12,53'
        ];

        $this->assertFailsWith($input, $rules, 'datetime');

    }

    /**
     * @test
     */
    public function it_validates_after()
    {
        $rules = [
            'created'  => 'after:2020-05-22'
        ];

        $input = [
            'created'     => '2021-05-23'
        ];

        $this->assertPasses($input, $rules);

        $input = [
            'created'     => '2020-05-21'
        ];

        $this->assertFailsWith($input, $rules, 'after');

        $rules = [
            'created'  => 'after:2020-05-22,m/d/Y'
        ];

        $input = [
            'created'     => '05/23/2020'
        ];

        $this->assertPasses($input, $rules);
    }

    /**
     * @test
     */
    public function validate_validates_base_rules_with_invalid_data()
    {

        $rules = [
            'login'     => 'required|min:5|max:64',
            'email'     => 'required|email',
            'signature' => 'min:3|max:255',
            'type_id'   => 'in:1,2,3',
            'path'      => 'alpha_dash'
        ];

        $input = [
            'login'     => 'mt',
            'signature' => 'Yo',
            'type_id'   => 4,
            'path'      => 'Höhö:'
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
                'in'       => [1,2,3]
            ],
            'path'      => [
                'alpha_dash' => []
            ]
        ];

        $failures = [
            'login'    => [
                'min' => [5]
            ],
            'email'    => [
                'required' => [] // stops after required
            ],
            'signature' => [
                'min' => [3]
            ],
            'type_id' => [
                'in' => [1,2,3]
            ],
            'path'      => [
                'alpha_dash' => []
            ]
        ];

        try {

            $validator = $this->make($rules);
            $validator->validate($input);
            $this->fail('validate() throws does not throw an exception even if data is invalid');

        } catch (Validation $v) {
            $this->assertEquals($parsed, $v->rules());
            $this->assertEquals($failures, $v->failures());
        }
    }

    /**
     * @test
     */
    public function validate_stops_when_rule_not_required()
    {

        $rules = [
            'login'     => 'required|min:5|max:64',
            'email'     => 'required|email',
            'signature' => 'min:3|max:255',
            'type_id'   => 'in:1,2,3',
            'path'      => 'alpha_dash'
        ];

        $input = [
            'login'     => 'mt',
            'type_id'   => 4,
            'path'      => 'Höhö:'
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
                'in'       => [1,2,3]
            ],
            'path'      => [
                'alpha_dash' => []
            ]
        ];

        $failures = [
            'login'    => [
                'min' => [5]
            ],
            'email'    => [
                'required' => [] // stops after required
            ],
            'type_id' => [
                'in' => [1,2,3]
            ],
            'path'      => [
                'alpha_dash' => []
            ]
        ];

        try {

            $validator = $this->make($rules);
            $validator->validate($input);
            $this->fail('validate() throws does not throw an exception even if data is invalid');

        } catch (Validation $v) {
            $this->assertEquals($parsed, $v->rules());
            $this->assertEquals($failures, $v->failures());
        }
    }

    /**
     * @test
     */
    public function validate_required_if_without_value_when_found()
    {

        $rules = [
            'login'     => 'required_if:email|min:5|max:64',
            'email'     => 'required|email'
        ];

        $input = [
            'email'     => 'mt@web.de',
            'type_id'   => 4,
            'path'      => 'Höhö:'
        ];

        $failures = [
            'login'    => [
                'required_if' => ['email']
            ]
        ];

        try {
            $this->make($rules)->validate($input);
            $this->fail('validate() throws does not throw an exception even if data is invalid');

        } catch (Validation $v) {
            $this->assertEquals($failures, $v->failures());
        }

    }

    /**
     * @test
     */
    public function validate_required_if_without_value_on_not_found()
    {

        $rules = [
            'login'     => 'required_if:email|min:5|max:64',
            'email'     => 'email'
        ];

        $input = [
            'type_id'   => 4,
            'path'      => 'Höhö:'
        ];

        try {
            $this->make($rules)->validate($input);
        } catch (Validation $v) {
            $this->fail('required_if should not fail if other value is not present');
        }

    }

    /**
     * @test
     */
    public function validate_required_if_with_value_when_equals()
    {

        $rules = [
            'login'     => 'required_if:email,mt@web.de|min:5|max:64',
            'email'     => 'required|email'
        ];

        $input = [
            'email'     => 'mt@web.de',
            'type_id'   => 4,
            'path'      => 'Höhö:'
        ];

        $failures = [
            'login'    => [
                'required_if' => ['email', 'mt@web.de']
            ]
        ];

        try {
            $this->make($rules)->validate($input);
            $this->fail('validate() throws does not throw an exception even if data is invalid');

        } catch (Validation $v) {
            $this->assertEquals($failures, $v->failures());
        }

    }

    /**
     * @test
     */
    public function validate_required_if_with_value_when_not_equals()
    {

        $rules = [
            'login'     => 'required_if:email,mt@web.de|min:5|max:64',
            'email'     => 'required|email'
        ];

        $input = [
            'email'     => 'ft@web.de',
            'type_id'   => 4,
            'path'      => 'Höhö:'
        ];

        try {
            $this->make($rules)->validate($input);
        } catch (Validation $v) {
            $this->fail('validate() throws an exception when required_if does not match the passed value');
        }
    }

    /**
     * @test
     */
    public function validate_required_unless_without_value_when_found()
    {

        $rules = [
            'login'     => 'required_unless:email|min:5|max:64',
            'email'     => 'email'
        ];

        $input = [
            'email'     => 'mt@web.de',
            'type_id'   => 4,
            'path'      => 'Höhö:'
        ];

        try {
            $this->make($rules)->validate($input);
        } catch (Validation $v) {
            $this->fail('required_unless should not fail when other should not be present and it is');
        }

    }

    /**
     * @test
     */
    public function validate_required_unless_without_value_when_not_found()
    {

        $rules = [
            'login'     => 'required_unless:email|min:5|max:64',
            'email'     => 'email'
        ];

        $input = [
            'type_id'   => 4,
            'path'      => 'Höhö:'
        ];

        $failures = [
            'login'    => [
                'required_unless' => ['email']
            ]
        ];

        try {
            $this->make($rules)->validate($input);
            $this->fail('required_unless should fail when other should not be present and is not');
        } catch (Validation $v) {
            $this->assertEquals($failures, $v->failures());
        }

    }

    /**
     * @test
     */
    public function validate_required_unless_without_values_when_found()
    {

        $rules = [
            'first_name'     => 'required_unless:company|min:1|max:64',
            'last_name'      => 'required_unless:company|min:1|max:64',
            'company'        => 'required_unless:first_name|min:1|max:64',
        ];

        $input = [
            'company'     => 'Toon Enterprises',
        ];

        try {
            $this->make($rules)->validate($input);
        } catch (Validation $v) {
            $this->fail('required_unless should not fail when other should not be present and is not');
        }

    }

    /**
     * @test
     */
    public function validate_required_unless_without_values_when_not_found()
    {

        $rules = [
            'first_name'     => 'required_unless:company|min:1|max:64',
            'last_name'      => 'required_unless:company|min:1|max:64',
            'company'        => 'required_unless:first_name|min:1|max:64',
        ];

        $input = [
            'last_name'     => 'Tils',
        ];

        $failures = [
            'first_name'    => [
                'required_unless' => ['company']
            ],
            'company'    => [
                'required_unless' => ['first_name']
            ]
        ];

        try {
            $this->make($rules)->validate($input);
            $this->fail('required_unless should not fail when other should not be present and is not');
        } catch (Validation $v) {
            $this->assertEquals($failures, $v->failures());

        }

    }

    public function test_validate_forwards_to_passed_callable()
    {

        $handler = new LoggingCallable(function (Validation $validation, array $input, array $rules, AppliesToResource $resource, $locale) {
            return $input;
        });

        $rules = ['password' => 'required'];
        $parsed = ['password' => ['required' => []]];
        $input = ['password' => 'blabla'];
        $resource = new NamedObject(null, 'king', 'category');
        $formats = [\Ems\Contracts\Validation\Validator::LOCALE, 'cz'];

        $validator = $this->make($rules, NamedObject::class, $handler);

        $this->assertEquals($input, $validator->validate($input, $resource, $formats));
        $this->assertInstanceOf(Validation::class, $handler->arg(0));
        $this->assertEquals($input, $handler->arg(1));
        $this->assertEquals($parsed, $handler->arg(2));
        $this->assertSame($resource, $handler->arg(3));
        $this->assertEquals($formats, $handler->arg(4));
    }

    public function test_validate_throws_validation_exception_validation_has_count()
    {

        $handler = new LoggingCallable(function (Validation $validation, array $input, array $rules, AppliesToResource $resource, $locale) {
            $validation->addFailure('password', 'between', [1,24]);
            return $input;
        });

        $rules = ['password' => 'required'];
        $parsed = ['password' => ['required' => []]];
        $input = ['password' => 'blabla'];

        $failures = [
            'password' => [
                'between' => [1,24]
            ]
        ];

        $resource = new NamedObject(null, 'king', 'category');
        $locale = 'cz';

        $validator = $this->make($rules, NamedObject::class, $handler);

        try {

            $validator->validate($input, $resource, [\Ems\Contracts\Validation\Validator::LOCALE => $locale]);
            $this->fail("Validator had to throw an validation exception");

        } catch (Validation $validation) {
            $this->assertEquals($failures, $validation->failures());
        }

        $this->assertInstanceOf(Validation::class, $handler->arg(0));
        $this->assertEquals($input, $handler->arg(1));
        $this->assertEquals($parsed, $handler->arg(2));
        $this->assertSame($resource, $handler->arg(3));
        $this->assertEquals([\Ems\Contracts\Validation\Validator::LOCALE => $locale], $handler->arg(4));
    }

    public function test_rules_returns_parsedRules()
    {

        $rules = [
            'login'     => 'required|min:5|max:64',
            'email'     => 'required|email',
            'signature' => 'min:3|max:255',
            'type_id'   => 'in:1,2,3|in-between:1,3'
        ];

        $validator = $this->make($rules);

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

        $this->assertEquals($parsed, $validator->rules());

    }

    public function test_merge_merges_rules()
    {

        $handler = new LoggingCallable(function (Validation $validation, array $input, array $rules, $resource, $parameters) {
            return $input;
        });

        $rules = ['password' => 'required'];

        $input = ['password' => 'blabla'];

        $resource = new NamedObject();

        $parameters = [\Ems\Contracts\Validation\Validator::LOCALE => 'cz'];

        $parsed = [
            'password' => [
                'min'      => [3]
            ],
            'login'    => [
                'max'      => [64]
            ]
        ];

        $validator = $this->make($rules, NamedObject::class, $handler);

        $validator->mergeRules(['password' => 'min:3', 'login' => 'max:64']);

        $this->assertEquals($input, $validator->validate($input, $resource, $parameters));
        $this->assertInstanceOf(Validation::class, $handler->arg(0));
        $this->assertEquals($input, $handler->arg(1));
        $this->assertEquals($parsed, $handler->arg(2));
        $this->assertSame($resource, $handler->arg(3));
        $this->assertEquals($parameters, $handler->arg(4));
    }

    public function test_get_and_set_ormClass()
    {
        $validator = $this->make(['password' => 'min:3'], self::class);
        $this->assertEquals(self::class, $validator->ormClass());
    }

    public function test_rules_fires_hooks_once_when_parsing_rules()
    {

        $validator = $this->make();

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

        $validator->mergeRules($rules);

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

            $validator = $this->make($rules);

            $validator->onBefore('validate', $breaker);

            $validator->validate($input, $country);

            $this->fail('The injected exception throw was not performed');

        } catch (BreakException $e) {
            $this->assertEquals($awaitedRules, $breaker->arg(1));
        }
    }

    public function test_missing_values_are_not_producing_validation_when_parent_not_required()
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

        $country = (new GenericEntity())->makeNew(false);

        try {
            $validator = $this->make($rules);
            $validator->validate($input, $country);

        } catch (ValidationException $e) {
            $this->fail('The validation failed: ' . var_export($e->failures(), true));
        }

        $rules = [
            'name'              => 'min:2|max:255',
            'iso_code'          => 'min:2|max:2',
            'password'          => 'required',
            'address'           => 'required',
            'address.country'   => 'min:2|max:255',
            'address.street'    => 'min:2|max:255',
            'address.location.name' => 'min:2|max:255',
            'category.id'       => 'min:2|max:255',
            'profile.nickname'  => 'min:2|max:255'
        ];

        try {

            $validator = $this->make($rules);

            $validator->validate($input);
            $this->fail('The validation should be failing because parent required was not found');

        } catch (ValidationException $e) {
            //print_r($e->failures());
        }
    }

    public function test_missing_values_are_producing_validation_when_parent_required()
    {

        $rules = [
            'name'              => 'min:2|max:255',
            'iso_code'          => 'min:2|max:2',
            'address'           => 'required',
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

        try {
            $validator = $this->make($rules);
            $validator->validate($input);

        } catch (ValidationException $e) {
            $this->assertArrayHasKey('address', $e->failures(), 'The required address validation did not fail');
        }

     }

    public function test_missing_required_values_are_not_producing_validation_when_parent_not_required()
    {

        $rules = [
            'name'                  => 'min:2|max:255',
            'iso_code'              => 'min:2|max:2',
            'address.country'       => 'required_if:address|min:2|max:255',
            'address.street'        => 'min:2|max:255',
            'address.location.name' => 'min:2|max:255',
            'category.id'           => 'min:2|max:255',
            'profile.nickname'      => 'min:2|max:255'
        ];

        $input = [
            'name'      => 'France',
            'iso_code'  => 'fr',
            'profile'   => [
                'nickname' => 'bla'
            ]
        ];

        try {
            $validator = $this->make($rules);
            $validator->validate($input);

        } catch (ValidationException $e) {
            $this->fail('Validation should not fail on required attribute if parent is not required:' . var_export($e->failures(), true));
        }

    }

    public function test_validateForbidden()
    {

        $handler = new LoggingCallable(function (Validation $validation, array $input, array $rules, $ormObject=null, $formats=[]) {
            return $input;
        });

        $rules = ['created_at' => 'forbidden'];
        $input = ['password' => 'blabla'];
        $resource = new NamedObject(15, 'king', 'category');
        $formats = [\Ems\Contracts\Validation\Validator::LOCALE=>'cz'];

        $validator = $this->make($rules, NamedObject::class, $handler);

        $this->assertEquals($input, $validator->validate($input, $resource, $formats));

        try {
            $validator->validate(['created_at'=>new \DateTime], $resource, $formats);
            $this->fail('Validation should fail with forbidden attributes');
        } catch (Validation $e) {

            $this->assertEquals([
                                    'created_at' => ['forbidden'=>[]]
                                ],
                                $e->failures());

        }
    }

    /**
     * @test
     */
    public function required_without_not_required_array_throws_no_exception()
    {
        $rules = [
            'name' => 'string',
            'tags' => 'array'
        ];
        $input = ['name' => 'Michael'];
        $this->assertEquals($input, $this->make($rules)->validate($input));
    }

    /**
     * @test
     */
    public function required_without_required_array_throws_exception()
    {
        $rules = [
            'name' => 'string',
            'tags' => 'required|array'
        ];
        $input = ['name' => 'Michael'];
        $this->expectException(ValidationException::class);
        $this->assertEquals($input, $this->make($rules)->validate($input));
    }

    /**
     * @test
     */
    public function required_with_array_throws_no_exception()
    {
        $rules = [
            'name' => 'string',
            'tags' => 'required|array'
        ];
        $input = ['name' => 'Michael', 'tags' => ['old','green']];
        $this->assertEquals($input, $this->make($rules)->validate($input));
    }

    /**
     * @test
     */
    public function required_with_no_array_throws_exception()
    {
        $rules = [
            'name' => 'string',
            'tags' => 'required|array'
        ];
        $input = ['name' => 'Michael', 'tags' => 'Thomas'];
        $this->expectException(ValidationException::class);
        $this->assertEquals($input, $this->make($rules)->validate($input));
    }

    /**
     * @test
     */
    public function required_with_min_array_size()
    {
        $rules = [
            'name' => 'string',
            'tags' => 'required|array|min:3'
        ];
        $input = ['name' => 'Michael', 'tags' => ['old','green']];
        $this->assertFailsWith($input, $rules, 'min');
    }

    /**
     * @test
     */
    public function required_with_max_array_size()
    {
        $rules = [
            'name' => 'string',
            'tags' => 'required|array|max:2'
        ];
        $input = ['name' => 'Michael', 'tags' => ['old','green','whoop']];
        $this->assertFailsWith($input, $rules, 'max');
    }

    /**
     * @test
     */
    public function test_array_items_rule()
    {
        $rules = [
            'name' => 'string',
            'tags' => 'required|array|min:2',
            'tags.*' => 'max:4'
        ];
        $input = ['name' => 'Michael', 'tags' => ['old','green','whoop']];
        $this->assertFailsWith($input, $rules, 'max');
    }

    /**
     * @test
     */
    public function test_nested_jsonpath_rule()
    {
        $rules = [
            'projects[*].name' => 'numeric'
        ];
        $input = [
            'name' => 'Michael',
            'projects' => [
                [
                    'id' => 14,
                    'name'  => 'Refactor complicated Validators'
                ],
                [
                    'id' => 22,
                    'name'  => 'Publish version one'
                ]
            ]
        ];
        $failures = $this->assertFailsWith($input, $rules, 'numeric');
        $this->assertEquals([
            'projects[0].name' => ['numeric'=>[]],
            'projects[1].name' => ['numeric'=>[]],
                            ], $failures);

        $rules = [
            'projects[*].name' => 'min:20'
        ];

        $failures = $this->assertFailsWith($input, $rules, 'min');

        $this->assertEquals([
                                'projects[1].name' => ['min'=>['20']],
                            ], $failures);
    }

    public function test_cast_scalar_rules()
    {
        $rules = [
            'id'        => 'int',
            'weight'    => 'numeric',
            'name'      => 'string',
            'married'   => 'bool'
        ];
        $validator = $this->make($rules);
        $input = [
            'id'    => '554',
            'weight'    => '90.75',
            'name'      => 'Uncle Tommy',
            'married'   => "1"
        ];
        $awaited = [
            'id'        => 554,
            'weight'    => 90.75,
            'name'      => 'Uncle Tommy',
            'married'   => true
        ];
        $this->assertSame($awaited, $validator->validate($input));
    }

    public function test_cast_date_values()
    {
        $rules = [
            'created'   => 'datetime',
            'birthday'  => 'date',
            'lunch'     => 'time',
            'meet_at'   => 'after:2022-05-31',
            'registered'    => 'before:now'
        ];
        $input = [
            'created'   => '2022-05-14 08:55:23',
            'birthday'  => '1976-05-31',
            'lunch'     => '12:05',
            'meet_at'   => '2022-06-08 07:00',
            'registered'    => '2010-08-03 22:15:11'
        ];
        $validator = $this->make($rules);
        $casted = $validator->validate($input);
        $this->assertInstanceOf(DateTime::class, $casted['created']);
        $this->assertEquals($input['created'], $casted['created']->format('Y-m-d H:i:s'));
        $this->assertInstanceOf(DateTime::class, $casted['birthday']);
        $this->assertEquals($input['birthday'], $casted['birthday']->format('Y-m-d'));
        $this->assertEquals('12:05', $casted['lunch']);
        $this->assertInstanceOf(DateTime::class, $casted['meet_at']);
        $this->assertEquals($input['meet_at'], $casted['meet_at']->format('Y-m-d H:i'));
        $this->assertInstanceOf(DateTime::class, $casted['registered']);
        $this->assertEquals($input['registered'], $casted['registered']->format('Y-m-d H:i:s'));
    }

    public function test_cast_date_values_with_format()
    {
        $rules = [
            'created'   => 'datetime:YmdHis',
            'birthday'  => 'date:d Y m',
            'lunch'     => 'time:H.i.s',
            'meet_at'   => 'after:2022-05-31,Y/m/d',
            'registered'    => 'before:2022-05-31,m-d-Y'
        ];
        $input = [
            'created'   => '20220514085523',
            'birthday'  => '31 1976 05',
            'lunch'     => '12.05.01',
            'meet_at'   => '2022/06/08',
            'registered'    => '08-03-2010'
        ];
        $validator = $this->make($rules);
        try {
            $casted = $validator->validate($input);
        } catch (ValidationException $e) {
            print_r($e->failures());
        }

        $this->assertInstanceOf(DateTime::class, $casted['created']);
        $this->assertEquals($input['created'], $casted['created']->format('YmdHis'));
        $this->assertInstanceOf(DateTime::class, $casted['birthday']);
        $this->assertEquals($input['birthday'], $casted['birthday']->format('d Y m'));
        $this->assertEquals('12.05.01', $casted['lunch']);
        $this->assertInstanceOf(DateTime::class, $casted['meet_at']);
        $this->assertEquals($input['meet_at'], $casted['meet_at']->format('Y/m/d'));
        $this->assertInstanceOf(DateTime::class, $casted['registered']);
        $this->assertEquals($input['registered'], $casted['registered']->format('m-d-Y'));
    }

    protected function make(array $rules=[], string $ormClass='', callable $baseValidator=null)
    {
        return new Validator($rules, $ormClass, $baseValidator);
    }

    /**
     * @param array $input
     * @param array $rules
     * @return void
     */
    protected function assertPasses(array $input, array $rules, $ormObject=null, array $formats=[])
    {
        try {
            $this->assertTrue(is_array($this->make($rules)->validate($input, $ormObject, $formats)));
        } catch (ValidationException $e) {
            $this->fail('Validation should pass but fails with ' . var_export($e->failures(), true));
        }
    }

    /**
     * Assert the validation of $input with $rules produces a validation error
     * with $rule.
     *
     * @param array $input
     * @param array $rules
     * @param string $rule
     * @param string $key (optional)
     * @param null $ormObject
     * @param array $formats
     * @return array The failed validation
     */
    protected function assertFailsWith(array $input, array $rules, string $rule, string $key='', $ormObject=null, array $formats=[]) : array
    {
        try {
            $this->make($rules)->validate($input, $ormObject, $formats);
            $this->fail("Validating for $rule should fail");
        } catch (ValidationException $e) {
            //
        }

        $failures = $e->failures();

        if ($key) {
            if (!isset($failures[$key])) {
                $this->fail("Validating for $rule did not fail on key '$key'");
            }
            $failures = [$failures[$key]];
        }

        $failedRules = [];
        $success = false;

        foreach ($failures as $key=>$rules) {
            foreach ($rules as $rulesRule=>$params) {
                $failedRules[] = $rulesRule;
                if ($rulesRule == $rule) {
                    $success = true;
                    break;
                }
            }
        }
        if ($success) {
            return $failures;
        }
        $this->fail("Validating for rule $rule did not fail on $rule but on " . implode(',',$failedRules));
    }
}

class BreakException extends \Exception
{
}