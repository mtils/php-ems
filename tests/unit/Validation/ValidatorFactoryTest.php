<?php


namespace Ems\Validation;

use Ems\Contracts\Validation\ValidatorFactory as ValidatorFactoryContract;
use Ems\Contracts\Core\Extendable;
use Ems\Contracts\Core\AppliesToResource;
use Ems\Contracts\Core\SupportsCustomFactory;
use Ems\Contracts\Validation\ValidationConverter as ConverterContract;
use Ems\Contracts\Validation\Validation;
use Ems\Validation\ValidationException;

use Ems\Core\NamedObject;

/**
 * @group validation
 **/
class ValidatorFactoryTest extends \Ems\TestCase
{
    public function test_implements_interface()
    {
        $this->assertInstanceOf(ValidatorFactoryContract::class, $this->newFactory());
        $this->assertInstanceOf(SupportsCustomFactory::class, $this->newFactory());
    }

    public function test_make_forwards_to_first_factory()
    {
        $factory = $this->newFactory();
        $factory->add(new GenericValidatorFactory);

        $rules = [
            'login' => 'required|min:3'
        ];

        $this->assertInstanceOf(GenericValidator::class, $factory->make($rules));
    }

    public function test_make_forwards_to_first_factory_if_no_direct_resource_validator_setted()
    {
        $factory = $this->newFactory();
        $factory->add(new GenericValidatorFactory);

        $resource = new NamedObject(12, 'name', 'users');

        $rules = [
            'login' => 'required|min:3'
        ];

        $this->assertInstanceOf(GenericValidator::class, $factory->make($rules, $resource));
    }

    /**
     * @expectedException Ems\Contracts\Core\Errors\Unsupported
     **/
    public function test_make_throws_exception_if_passing_rules_to_validator_which_not_supports_setting_of_rules()
    {
        $factory = $this->newFactory();
        $factory->add(new GenericValidatorFactory(function () {
            return new DoesNotSupportRuleSetting();
        }));

        $resource = new NamedObject(12, 'name', 'users');

        $rules = [
            'login' => 'required|min:3'
        ];

        $factory->make($rules, $resource);

    }

    public function test_make_forwards_to_resource_validator_if_setted()
    {
        $factory = $this->newFactory();
        $factory->add(new GenericValidatorFactory);

        $resource = new NamedObject(12, 'name', 'users');

        $rules = [
            'login' => 'required|min:3'
        ];

        $validator = new GenericValidator;

        $creator = function () use ($validator) {
            return $validator;
        };

        $factory->setForResource($resource, $creator);

        $this->assertSame($validator, $factory->make($rules, $resource));
    }

    public function test_hasForResource_returns_correct_state()
    {

        $factory = $this->newFactory();
        $resource = 'users';
        $this->assertFalse($factory->hasForResource($resource));
        $factory->setForResource($resource, GenericValidator::class);
        $this->assertTrue($factory->hasForResource($resource));
    }

    public function test_getForResource_returns_added_validator_as_callable()
    {

        $factory = $this->newFactory();
        $resource = 'users';
        $factory->setForResource($resource, GenericValidator::class);
        $creator = $factory->getForResource($resource);
        $this->assertInstanceOf(GenericValidator::class, $creator());
    }

    public function test_setForResource_with_callable_creator()
    {

        $factory = $this->newFactory();
        $resource = 'users';

        $creator = function () {
            return new GenericValidator;
        };

        $factory->setForResource($resource, $creator);

        $this->assertSame($creator, $factory->getForResource($resource));
    }

    public function test_setForResource_with_validator_instance()
    {

        $factory = $this->newFactory();
        $resource = 'users';

        $validator = new GenericValidator;

        $factory->setForResource($resource, $validator);

        $creator = $factory->getForResource($resource);

        $this->assertTrue(is_callable($creator));

        $createdValidator = $creator();

        $this->assertInstanceOf(GenericValidator::class, $createdValidator);
        $this->assertNotSame($validator, $createdValidator);
    }

    /**
     * @expectedException InvalidArgumentException
     **/
    public function test_setForResource_throws_exception_on_invalid_type()
    {
        $this->newFactory()->setForResource('users', 3.5);
    }

    public function test_unsetForResource_removes_setted_factory()
    {

        $factory = $this->newFactory();
        $resource = 'users';
        $this->assertFalse($factory->hasForResource($resource));
        $factory->setForResource($resource, GenericValidator::class);
        $this->assertTrue($factory->hasForResource($resource));
        $factory->unsetForResource($resource);
        $this->assertFalse($factory->hasForResource($resource));
    }

    /**
     * @expectedException Ems\Core\Exceptions\HandlerNotFoundException
     **/
    public function test_unsetForResource_throws_exception_if_not_setted()
    {

        $factory = $this->newFactory();
        $resource = 'users';
        $factory->unsetForResource($resource);
    }

    protected function newFactory()
    {
        return new ValidatorFactory();
    }
}

class ValidatorFactoryTestFactory implements ValidatorFactoryContract
{
    /**
     * {@inheritdoc}
     *
     * @param array             $rules
     * @param AppliesToResource $resource (optional
     *
     * @return Validator
     **/
    public function make(array $rules, AppliesToResource $resource=null)
    {
        return new GenericValidator($rules);
    }
}

class DoesNotSupportRuleSetting extends Validator
{

    public function resource()
    {
        return new NamedObject(1,'foo','foo');
    }

    public function canMergeRules(): bool
    {
        return false;
    }


    protected function validateByBaseValidator(
        Validation $validation,
        array $input,
        array $baseRules,
        $ormObject = null,
        array $formats = []
    ): array {
        return $input;
    }


}
