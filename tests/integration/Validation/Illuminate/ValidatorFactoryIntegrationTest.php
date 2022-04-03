<?php

namespace Ems\Validation\Illuminate;

use Ems\Contracts\Validation\ValidatorFactory as ValidationFactoryContract;
use Ems\Skeleton\Application;
use Ems\Testing\Eloquent\InMemoryConnection;
use Ems\Validation\Validator;
use Ems\Validation\ValidatorFactory as EmsValidatorFactory;
use Ems\XType\Eloquent\BaseModel;
use Ems\XType\Eloquent\Category;
use Ems\XType\Eloquent\User;
use Ems\XType\Illuminate\XTypeProviderValidatorFactory;
use Illuminate\Contracts\Translation\Translator as TranslatorContract;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;

require_once realpath(__DIR__ . '/../../XType/Eloquent/test_models.php');


/**
 * This test mainly assures that the service providers and bootstappers do
 * work in a laravel application.
 *
 * @group validation
 **/
class ValidatorFactoryIntegrationTest extends \Ems\LaravelIntegrationTest
{

    use InMemoryConnection;

    public function test_create_validator_by_rules()
    {
        $factory = $this->make();

        $rules = [
            'login' => 'required|min:3|max:128'
        ];

        $parsed = [
            'login' => [
                'required' => [],
                'min'      => ['3'],
                'max'      => ['128'],
            ]
        ];

        $validator = $factory->create($rules);

        $this->assertInstanceOf(Validator::class, $validator);
        $this->assertEquals($parsed, $validator->rules());
    }

    public function test_get_validator_by_xtype_of_model()
    {
        $factory = $this->make();

        $validator = $factory->get(User::class);

        $this->assertInstanceOf(Validator::class, $validator);

        $this->assertTrue(count($validator->rules()) > 4);
    }

    public function test_get_validator_by_xtype_of_model_and_merges_rules()
    {
        $factory = $this->make();

        $rules = [
            'external_id' => 'required|min:3|max:64',
            'some_other'  => 'numeric|between:1,6'
        ];

        /** @var Validator $validator */
        $validator = $factory->get(Category::class);
        $this->assertEquals(Category::class, $validator->ormClass());

        $validator->mergeRules($rules);

        $rules = $validator->rules();

        $this->assertTrue(count($rules) > 4);

        $awaited = [
            'external_id' => [
                'required' => [],
                'min'      => ['3'],
                'max'      => ['64']
            ],
            'some_other' => [
                'numeric' => [],
                'between' => ['1', '6']
            ]
        ];

        foreach ($awaited as $key=>$constraints) {
            $this->assertEquals($constraints, $rules[$key]);
        }
    }

    public function test_get_validator_by_custom_assigned_validator()
    {

        $factory = $this->make();

        if (!$factory instanceof \Ems\Validation\ValidatorFactory) {
            $this->fail("This test only works with ValidatorFactory");
        }
        $factory->register(Category::class, AlterableCategoryValidator::class);

        $rules = [
            'external_id' => 'required|min:3|max:64',
            'some_other'  => 'numeric|between:1,6'
        ];

        $validator = $factory->get( Category::class);
        $this->assertInstanceOf(AlterableCategoryValidator::class, $validator);
        $validator->mergeRules($rules);

        $rules = $validator->rules();

        $this->assertTrue(count($rules) > 2);

        $awaited = [
            'external_id' => [
                'required' => [],
                'min'      => ['3'],
                'max'      => ['64']
            ],
            'some_other' => [
                'numeric' => [],
                'between' => ['1', '6']
            ]
        ];

        foreach ($awaited as $key=>$constraints) {
            $this->assertEquals($constraints, $rules[$key]);
        }
    }

    public function test_get_validator_by_custom_assigned_generic_validator()
    {

        $factory = $this->make();

        if (!$factory instanceof \Ems\Validation\ValidatorFactory) {
            $this->fail("This test only works with ValidatorFactory");
        }

        $factory->register(Category::class, function (string $ormClass) {
            return new CategoryValidator([
                                     'external_id' => 'string|min:5|max:255',
                                     'name'        => 'string|min:10|max:255'
                                 ], $ormClass);
        });

        $rules = [
            'external_id' => 'required|min:3|max:64',
            'some_other'  => 'numeric|between:1,6'
        ];

        $validator = $factory->get(Category::class);
        $this->assertInstanceOf(CategoryValidator::class, $validator);

        $validator->mergeRules($rules);

        $rules = $validator->rules();

        $this->assertTrue(count($rules) > 2);

    }

    /**
     * Create the factory
     * @return ValidationFactoryContract
     */
    protected function make() : ValidationFactoryContract
    {
        return $this->laravel(ValidationFactoryContract::class);
    }

    protected function bootApplication(Application $app)
    {
        parent::bootApplication($app);
        $app->onAfter(EmsValidatorFactory::class, function (EmsValidatorFactory $factory) {
            $factory->register(BaseModel::class, function (string $ormClass) {
                /** @var XTypeProviderValidatorFactory $xtypeFactory */
                $xtypeFactory = $this->laravel(XTypeProviderValidatorFactory::class);
                return $xtypeFactory->validator($ormClass);
            });
        });
        $app->bind(TranslatorContract::class, function () {
            $loader = new ArrayLoader();
            return new Translator($loader, 'en');
        });
    }


}

class CategoryValidator extends Validator
{
    public function ormClass() : string
    {
        return Category::class;
    }
}

class AlterableCategoryValidator extends CategoryValidator
{
    protected $rules = [
        'external_id' => 'string|min:5|max:255',
        'name'        => 'string|min:10|max:255'
    ];
}
