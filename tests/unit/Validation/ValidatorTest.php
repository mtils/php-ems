<?php
/**
 *  * Created by mtils on 27.11.2021 at 20:40.
 **/

namespace Ems\Validation;

use Ems\Contracts\Validation\Validation;
use Ems\Contracts\Validation\Validator as ValidatorContract;
use Ems\TestCase;

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

        try {

            $validator = $this->make($rules);
            $validator->validate($input);
            $this->assertTrue(true);
        } catch (Validation $v) {
            $this->fail('validate() throws an exception even if data is valid');
        }
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


    protected function make(array $rules=[])
    {
        return new Validator($rules);
    }
}