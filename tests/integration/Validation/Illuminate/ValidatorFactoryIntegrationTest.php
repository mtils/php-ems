<?php

namespace Ems\Validation\Illuminate;

use Ems\Contracts\Validation\ValidatorFactory as ValidationFactoryContract;
use Illuminate\Contracts\Validation\Factory as IlluminateFactory;
use Ems\Testing\Eloquent\InMemoryConnection;
use Ems\XType\Eloquent\User;
use Ems\XType\Eloquent\Category;
use Ems\XType\Eloquent\ModelTypeFactoryTest;

require_once realpath(__DIR__ . '/../../XType/Eloquent/test_models.php');


/**
 * This test mainly assures that the service providers and bootstappers do
 * work in a laravel application.
 **/
class ValidatorFactoryIntegrationTest extends \Ems\LaravelIntegrationTest
{

    use InMemoryConnection;

    public function test_create_validator_by_rules()
    {
        $factory = $this->laravel(ValidationFactoryContract::class);

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

        $validator = $factory->make($rules);

        $this->assertInstanceOf(GenericValidator::class, $validator);
        $this->assertEquals($parsed, $validator->rules());
    }

    public function test_create_validator_by_xtype_of_model()
    {
        $factory = $this->laravel(ValidationFactoryContract::class);

        $rules = [
            'login' => 'required|min:3|max:128'
        ];

        $user = new User;

        $validator = $factory->make([], $user);

        $this->assertInstanceOf(GenericValidator::class, $validator);

        $this->assertTrue(count($validator->rules()) > 4);
    }

    public function test_create_validator_by_xtype_of_model_and_merges_rules()
    {
        $factory = $this->laravel(ValidationFactoryContract::class);

        $rules = [
            'external_id' => 'required|min:3|max:64',
            'some_other'  => 'numeric|between:1,6'
        ];

        $category = new Category;

        $validator = $factory->make($rules, $category);

        $this->assertInstanceOf(GenericValidator::class, $validator);
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

    public function test_create_validator_by_custom_assigned_validator()
    {

        $factory = $this->laravel(ValidationFactoryContract::class);

        $factory->setForResource('categories', AlterableCategoryValidator::class);

        $rules = [
            'external_id' => 'required|min:3|max:64',
            'some_other'  => 'numeric|between:1,6'
        ];

        $category = new Category;

        $validator = $factory->make($rules, $category);

        $this->assertInstanceOf(AlterableCategoryValidator::class, $validator);
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

    public function test_create_validator_by_custom_assigned_generic_validator()
    {

        $factory = $this->laravel(ValidationFactoryContract::class);

        $factory->setForResource('categories', CategoryValidator::class);

        $rules = [
            'external_id' => 'required|min:3|max:64',
            'some_other'  => 'numeric|between:1,6'
        ];

        $category = new Category;

        $validator = $factory->make([], $category);

        $this->assertInstanceOf(CategoryValidator::class, $validator);
        $rules = $validator->rules();

        $this->assertTrue(count($rules) > 4);

    }


}

class CategoryValidator extends GenericValidator
{
    
}

class AlterableCategoryValidator extends AlterableValidator
{

    public function resource()
    {
        return new Category;
    }

}
