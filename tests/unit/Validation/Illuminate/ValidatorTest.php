<?php


namespace Ems\Validation\Illuminate;

use Ems\Contracts\Core\Entity;
use Ems\Contracts\Validation\Validation;
use Ems\Contracts\Validation\Validator as ValidatorContract;
use Ems\XType\Illuminate\XTypeProviderValidatorFactory;
use Illuminate\Validation\Factory as IlluminateFactory;
use Illuminate\Contracts\Translation\Translator as TranslatorContract;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\LoaderInterface;
use Illuminate\Translation\Translator;
use Ems\Testing\LoggingCallable;
use Ems\Core\Helper;
use Ems\Core\NamedObject;
use Ems\Contracts\Core\AppliesToResource;
use Ems\XType\Eloquent\UniqueCountry;
use Ems\Validation\Validator;

require_once realpath(__DIR__ . '/../../../integration/XType/Eloquent/test_models.php');

/**
 * @group validation
 **/
class ValidatorTest extends \Ems\TestCase
{
    public function test_implements_interface()
    {
        $this->assertInstanceOf(
            ValidatorContract::class,
            $this->newValidator()
        );
    }

    public function test_validate_validates_base_rules_with_valid_data()
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

            $validator = $this->newValidator($rules);
            $validator->validate($input);

        } catch (Validation $v) {
            $this->fail('validate() throws an exception even if data is valid');
        }
    }

    public function test_validate_validates_base_rules_with_invalid_data()
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

            $validator = $this->newValidator($rules);
            $validator->validate($input);
            $this->fail('validate() throws does not throw an exception even if data is invalid');

        } catch (Validation $v) {
            $this->assertEquals($parsed, $v->rules());
            $this->assertEquals($failures, $v->failures());
        }
    }

    public function test_validate_custom_rules_and_valid_data()
    {
        $rules = [
            'login'         => 'required|manners',
            'age'           => 'in_between:18,46',
            'signature'     => 'user',
            'type_id'       => 'has_id:13',
            'path'          => 'exactly:/home/michael/.profile',
            'address.street'=> 'min:1|max:100'
        ];

        $resource = new NamedObject(13, 'name', 'user');

        $input = [
            'login'     => 'mt',
            'signature' => $resource,
            'type_id'   => 4,
            'path'      => '/home/michael/.profile',
            'address' => [
                'street' => 'Elm Street'
            ]
        ];

        $parsed = [
            'login' => [
                'required' => [],
                'manners'  => []
            ],
            'age' => [
                'in_between' => [18,46]
            ],
            'signature' => [
                'user'     => []
            ],
            'type_id'   => [
                'has_id'   => [13]
            ],
            'path'      => [
                'exactly' => ['/home/michael/.profile']
            ],
            'address.street' => [
                'min' => [1],
                'max' => [100]
            ]
        ];

        try {

            $validator = $this->newCustomValidator($rules);
            $validator->validate($input, $resource);

        } catch (Validation $v) {
            $this->fail('validate() throws an exception even if data is valid');

        }

    }

    public function test_validate_custom_rules_with_invalid_data()
    {
        $rules = [
            'login'         => 'required|manners',
            'age'           => 'in_between:18,46',
            'signature'     => 'user',
            'type_id'       => 'has_id:13',
            'path'          => 'exactly:/home/michael/.profile',
            'address.street'=> 'min:2|max:5'
        ];

        $resource = new NamedObject(14, 'name', 'account');

        $input = [
            'login'     => 'mr.shit',
            'age'       => 17,
            'signature' => 'no_user_resource',
            'type_id'   => 4,
            'path'      => 'Höhö:1',
            'address' => [
                'street' => 'Elm Street'
            ]
        ];

        $parsed = [
            'login' => [
                // 'required' => [], Login will be removed
                'manners'  => []
            ],
            'age' => [
                'in_between' => [18,46]
            ],
            'signature' => [
                'user'     => []
            ],
            'type_id'   => [
                'has_id'   => [13]
            ],
            'path'      => [
                'exactly' => ['/home/michael/.profile']
            ],
            'address.street' => [
                'min' => [2],
                'max' => [5]
            ]
        ];

        $failures = [
            'login' => [
                'manners' => []
            ],
            'age'   => [
                'in_between' => [18, 46]
            ],
            'signature' => [
                'user' => []
            ],
            'type_id' => [
                'has_id' => [13]
            ],
            'path' => [
                'exactly' => ['/home/michael/.profile']
            ],
            'address.street' => [
                'max' => [5]
            ]
        ];

        try {

            $validator = $this->newCustomValidator($rules);
            $validator->validate($input, $resource);
            $this->fail('validate() does not throw an exception even if data is invalid');

        } catch (Validation $v) {
            $this->assertEquals($parsed, $v->rules());
            $this->assertEquals($failures, $v->failures());
        }

    }

    public function test_prepareRulesForValidation_unique_constraint_in_new_resource()
    {

        $rules = [
            'name'      => 'min:2|max:255',
            'iso_code'  => 'min:2|max:2|unique'
        ];

        $input = [
            'name'      => 'France',
            'iso_code'  => 'fr'
        ];

        $awaitedRules = [
            'name' => [
                'min:2',
                'max:255'
            ],
            'iso_code' => [
                'min:2',
                'max:2',
                'unique:countries,iso_code'
            ]
        ];

        $country = new UniqueCountry();

        $usedRules = [];

        $factory = $this->mock(IlluminateFactory::class);
        $factory->shouldReceive('make')
            ->once()
            ->with($input, $awaitedRules)
            ->andReturnUsing(function ($input, $rules) use (&$usedRules) {
                $usedRules = $rules;
                throw new TestFinishedException();
            });

        $baseValidator = new IlluminateBaseValidator($factory);

        try {

            $validator = $this->newValidator($rules, UniqueCountry::class, $baseValidator);

            $validator->validate($input, $country);

            $this->fail('The injected exception throw was not performed');

        } catch (TestFinishedException $e) {
            $this->assertEquals($awaitedRules, $usedRules);
        }
    }

    public function test_prepareRulesForValidation_unique_constraint_in_existing_resource()
    {

        $rules = [
            'name'      => 'min:2|max:255',
            'iso_code'  => 'min:2|max:2|unique'
        ];

        $input = [
            'name'      => 'France',
            'iso_code'  => 'fr'
        ];

        $country = new UniqueCountry;
        $country->id = 45;
        $country->exists = true;

        $awaitedRules = [
            'name' => [
                'min:2',
                'max:255'
            ],
            'iso_code' => [
                'min:2',
                'max:2',
                'unique:countries,iso_code,45,id'
            ]
        ];

        $usedRules = [];

        $factory = $this->mock(IlluminateFactory::class);
        $factory->shouldReceive('make')
            ->once()
            ->with($input, $awaitedRules)
            ->andReturnUsing(function ($input, $rules) use (&$usedRules) {
                $usedRules = $rules;
                throw new TestFinishedException();
            });

        $baseValidator = new IlluminateBaseValidator($factory);

        try {

            $validator = $this->newValidator($rules, UniqueCountry::class, $baseValidator);

            $validator->validate($input, $country);

            $this->fail('The injected exception throw was not performed');

        } catch (TestFinishedException $e) {
            $this->assertEquals($awaitedRules, $usedRules);
        }
    }

    public function test_prepareRulesForValidation_unique_constraint_in_non_entity()
    {

        $rules = [
            'name'      => 'min:2|max:255',
            'iso_code'  => 'min:2|max:2|unique'
        ];

        $input = [
            'name'      => 'France',
            'iso_code'  => 'fr'
        ];

        $country = new NamedObject;

        $awaitedRules = [
            'name' => [
                'min:2',
                'max:255'
            ],
            'iso_code' => [
                'min:2',
                'max:2',
                'unique'
            ]
        ];

        $usedRules = [];

        $factory = $this->mock(IlluminateFactory::class);
        $factory->shouldReceive('make')
            ->once()
            ->with($input, $awaitedRules)
            ->andReturnUsing(function ($input, $rules) use (&$usedRules) {
                $usedRules = $rules;
                throw new TestFinishedException();
            });

        $baseValidator = new IlluminateBaseValidator($factory);

        try {

            $validator = $this->newValidator($rules, NamedObject::class, $baseValidator);

            $validator->validate($input, $country);

            $this->fail('The injected exception throw was not performed');

        } catch (TestFinishedException $e) {
            $this->assertEquals($awaitedRules, $usedRules);
        }
    }

    public function test_prepareRulesForValidation_unique_constraint_in_non_eloquent_entity()
    {

        $rules = [
            'name'      => 'min:2|max:255',
            'iso_code'  => 'min:2|max:2|unique'
        ];

        $input = [
            'name'      => 'France',
            'iso_code'  => 'fr'
        ];

        $country = new NamedEntity;

        $breaker = new LoggingCallable(function () {
            throw new TestFinishedException;
        });

        $awaitedRules = [
            'name' => [
                'min' => [2],
                'max' => [255]
            ],
            'iso_code' => [
                'min' => [2],
                'max' => [2],
                'unique' => []
            ]
        ];

        try {

            $validator = $this->newValidator($rules);

            $validator->onBefore('validate', $breaker);

            $validator->validate($input, $country);

            $this->fail('The injected exception throw was not performed');

        } catch (TestFinishedException $e) {
            $this->assertEquals($awaitedRules, $breaker->arg(1));
        }
    }

    public function test_prepareRulesForValidation_unique_constraint_nested_keys()
    {

        $rules = [
            'name'      => 'min:2|max:255',
            'address.iso_code'  => 'min:2|max:2|unique'
        ];

        $input = [
            'name'      => 'France',
            'iso_code'  => 'fr'
        ];

        $country = new UniqueCountry;

        $breaker = new LoggingCallable(function () {
            throw new TestFinishedException;
        });

        $awaitedRules = [
            'name' => [
                'min' => [2],
                'max' => [255]
            ],
            'address.iso_code' => [
                'min' => [2],
                'max' => [2],
                'unique' => []
            ]
        ];

        try {

            $validator = $this->newValidator($rules);

            $validator->onBefore('validate', $breaker);

            $validator->validate($input, $country);

            $this->fail('The injected exception throw was not performed');

        } catch (TestFinishedException $e) {
            $this->assertEquals($awaitedRules, $breaker->arg(1));
        }
    }

    protected function newValidator(array $rules=[], string $ormClass='', callable $baseValidator=null)
    {
        $baseValidator = $baseValidator ?: new IlluminateBaseValidator($this->newFactory());
        return new Validator($rules, $ormClass, $baseValidator);
    }

    protected function newCustomValidator(array $rules=[], IlluminateFactory $factory=null)
    {
        return new CustomValidator($rules, '', new IlluminateBaseValidator($factory ?: $this->newFactory()));
    }

    protected function newFactory(TranslatorContract $lang=null)
    {
        return new IlluminateFactory($lang ?: $this->newTranslator());
    }

    protected function newTranslator(LoaderInterface $loader=null)
    {
        return new Translator($loader ?: $this->newLoader(), 'en');
    }

    protected function newLoader()
    {
        return new ArrayLoader();
    }
}

class CustomValidator extends Validator
{

    /**
     * Validate if a string contains the word "shit"...a validator without
     * parameters
     *
     * @param array             $input
     * @param string            $key
     *
     * @return bool
     **/
    protected function validateManners(array $input, $key)
    {
        if (!$value = Helper::value($input, $key)) {
            return true;
        }

        if (!is_string($value) || !$value) {
            return true;
        }

        return (strpos($value, 'shit') === false);
    }

    /**
     * Validate if a value is between min and max (two parameters)
     *
     * @param array             $input
     * @param string            $key
     * @param string|int        $min
     * @param string|int        $max
     *
     * @return bool
     **/
    protected function validateInBetween(array $input, $key, $min, $max)
    {
        if (!$value = Helper::value($input, $key)) {
            return true;
        }

        if (!is_numeric($value)) {
            return true;
        }

        return ((int)$value >= $min) && ((int)$value <= $max);

    }

    /**
     * @param array                     $input
     * @param string                    $key
     * @param AppliesToResource|null    $ormObject (optional)
     *
     * @return bool
     **/
    protected function validateUser(array $input, $key, AppliesToResource $ormObject=null) : bool
    {
        if (!Helper::value($input, $key)) {
            return true;
        }

        if (!$ormObject) {
            return true;
        }

        return $ormObject->resourceName() == 'user';
    }

    protected function validateHasId(AppliesToResource $ormObject, $id)
    {
        return $ormObject->getId() == $id;
    }

    protected function validateIsIn(array $input, $key)
    {
        if (!$value = Helper::value($input, $key)) {
            return true;
        }
        $args = func_get_args();
        array_shift($args);
        array_shift($args);
        return in_array($value, $args);
    }

    protected function validateExactly($value, $target)
    {
        return $value == $target;
    }

}

class TestFinishedException extends \Exception{};

class NamedEntity extends NamedObject implements Entity
{
    /**
     * @return bool
     **/
    public function isNew()
    {
        return true;
    }

    /**
     *  @param string|array $attributes (optional)
     *
     * @return bool
     **/
    public function wasModified($attributes=null)
    {
        return true;
    }
}
