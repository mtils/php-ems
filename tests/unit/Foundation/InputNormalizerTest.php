<?php


namespace Ems\Foundation;

use Ems\Contracts\Foundation\InputNormalizer as InputNormalizerContract;
use Ems\Contracts\Foundation\InputProcessor as InputProcessorContract;
use Ems\Contracts\Validation\Validator;
use Ems\Contracts\Validation\ValidatorFactory;
use Ems\Core\NamedObject;
use Ems\Testing\LoggingCallable;


class InputNormalizerTest extends \Ems\TestCase
{
    public function test_implements_interface()
    {
        $this->assertInstanceOf(InputNormalizerContract::class, $this->newNormalizer());
    }

    public function test_normalize_with_adjust_validate_cast()
    {
        $adjuster = $this->mock(InputProcessorContract::class);
        $validator = $this->mock(Validator::class);
        $validatorFactory = $this->mock(ValidatorFactory::class);
        $caster = $this->mock(InputProcessorContract::class);

        $normalizer = $this->newNormalizer($validatorFactory, $adjuster, $caster);

        $input = ['a' => 1, 'b' => 2];
        $adjusted = ['a' => 1, 'b' => 2, 'c' => 3];
        $casted = ['a' => 1];
        $rules = ['a' => 'min:2'];

        $resource = new NamedObject;

        $adjuster->shouldReceive('process')
                 ->with($input, $resource, null)
                 ->once()
                 ->andReturn($adjusted);

        $validatorFactory->shouldReceive('create')
                         ->with($rules, NamedObject::class)
                         ->andReturn($validator);

        $validator->shouldReceive('validate')
                  ->with($adjusted, $resource, null)
                  ->once()
                  ->andReturn($input);

        $caster->shouldReceive('process')
               ->with($adjusted, $resource, null)
               ->once()
               ->andReturn($casted);

        $awaited = $normalizer->adjust()
                              ->validate($rules)
                              ->cast()
                              ->normalize($input, $resource);

        $this->assertEquals($awaited, $casted);
    }

    public function test_normalize_with_adjust_validate_cast_if_none_passed()
    {
        $adjuster = $this->mock(InputProcessorContract::class);
        $validator = $this->mock(Validator::class);
        $validatorFactory = $this->mock(ValidatorFactory::class);
        $caster = $this->mock(InputProcessorContract::class);

        $normalizer = $this->newNormalizer($validatorFactory, $adjuster, $caster);

        $input = ['a' => 1, 'b' => 2];
        $adjusted = ['a' => 1, 'b' => 2, 'c' => 3];
        $casted = ['a' => 1];

        $resource = new NamedObject;

        $adjuster->shouldReceive('process')
                 ->with($input, $resource, null)
                 ->once()
                 ->andReturn($adjusted);

        $validatorFactory->shouldReceive('create')
                         ->with([], NamedObject::class)
                         ->andReturn($validator);

        $validator->shouldReceive('validate')
                  ->with($adjusted, $resource, null)
                  ->once()
                  ->andReturn($input);

        $caster->shouldReceive('process')
               ->with($adjusted, $resource, null)
               ->once()
               ->andReturn($casted);

        $awaited = $normalizer->normalize($input, $resource);

        $this->assertEquals($awaited, $casted);
    }

    public function test_normalize_without_adjust_if_explicit_disabled()
    {
        $adjuster = $this->mock(InputProcessorContract::class);
        $validator = $this->mock(Validator::class);
        $validatorFactory = $this->mock(ValidatorFactory::class);
        $caster = $this->mock(InputProcessorContract::class);

        $normalizer = $this->newNormalizer($validatorFactory, $adjuster, $caster);

        $input = ['a' => 1, 'b' => 2];
        $adjusted = ['a' => 1, 'b' => 2, 'c' => 3];
        $casted = ['a' => 1];
        $rules = ['a' => 'min:2'];

        $resource = new NamedObject;

        $adjuster->shouldReceive('process')
                 ->never();

        $validatorFactory->shouldReceive('create')
                         ->with($rules, NamedObject::class)
                         ->andReturn($validator);

        $validator->shouldReceive('validate')
                  ->with($input, $resource, null)
                  ->once()
                  ->andReturn($input);

        $caster->shouldReceive('process')
               ->with($input, $resource, null)
               ->once()
               ->andReturn($casted);

        $awaited = $normalizer->validate($rules)
                              ->adjust(false)
                              ->cast()
                              ->normalize($input, $resource);

        $this->assertEquals($awaited, $casted);
    }

    public function test_normalize_with_custom_adjuster()
    {
        $adjuster = $this->mock(InputProcessorContract::class);
        $validator = $this->mock(Validator::class);
        $validatorFactory = $this->mock(ValidatorFactory::class);
        $caster = $this->mock(InputProcessorContract::class);

        $normalizer = $this->newNormalizer($validatorFactory, $adjuster, $caster);

        $input = ['a' => 1, 'b' => 2];
        $adjusted = ['a' => 1, 'b' => 2, 'c' => 3];
        $casted = ['a' => 1];
        $rules = ['a' => 'min:2'];

        $resource = new NamedObject;

        $customAdjuster = $this->mock(InputProcessorContract::class);

        $adjuster->shouldReceive('process')
                 ->never();

        $customAdjuster->shouldReceive('process')
                       ->with($input, $resource, null)
                       ->once()
                       ->andReturn($adjusted);

        $validatorFactory->shouldReceive('create')
                         ->with($rules, NamedObject::class)
                         ->andReturn($validator);

        $validator->shouldReceive('validate')
                  ->with($adjusted, $resource, null)
                  ->once()
                  ->andReturn($input);

        $caster->shouldReceive('process')
               ->with($adjusted, $resource, null)
               ->once()
               ->andReturn($casted);

        $awaited = $normalizer->adjust($customAdjuster)
                              ->validate($rules)
                              ->cast()
                              ->normalize($input, $resource);

        $this->assertEquals($awaited, $casted);
    }

    public function test_normalize_with_modified_adjust_chain()
    {
        //$adjuster = $this->mock(InputProcessorContract::class);

        $adjuster = new InputProcessor;

        $validator = $this->mock(Validator::class);
        $validatorFactory = $this->mock(ValidatorFactory::class);
        $caster = $this->mock(InputProcessorContract::class);

        $normalizer = $this->newNormalizer($validatorFactory, $adjuster, $caster);

        $input = ['a' => 1, 'b' => 2];
        $adjusted = ['a' => 1, 'b' => 2, 'c' => 3];
        $casted = ['a' => 1];
        $rules = ['a' => 'min:2'];

        $resource = new NamedObject;


        $adjuster->extend('foo', function () use ($adjusted) {
            return $adjusted;
        });

        $validatorFactory->shouldReceive('create')
            ->with($rules, NamedObject::class)
            ->andReturn($validator);

        $validator->shouldReceive('validate')
                  ->with($adjusted, $resource, null)
                  ->once()
                  ->andReturn($input);

        $caster->shouldReceive('process')
               ->with($adjusted, $resource, null)
               ->once()
               ->andReturn($casted);

        $awaited = $normalizer->adjust('foo')
                              ->validate($rules)
                              ->cast()
                              ->normalize($input, $resource);

        $this->assertEquals($awaited, $casted);
    }

    public function test_normalize_with_adjust_validate_cast_and_explicit_validate()
    {
        $adjuster = $this->mock(InputProcessorContract::class);
        $validator = $this->mock(Validator::class);
        $validatorFactory = $this->mock(ValidatorFactory::class);
        $caster = $this->mock(InputProcessorContract::class);

        $normalizer = $this->newNormalizer($validatorFactory, $adjuster, $caster);

        $input = ['a' => 1, 'b' => 2];
        $adjusted = ['a' => 1, 'b' => 2, 'c' => 3];
        $casted = ['a' => 1];
        $rules = ['a' => 'min:2'];

        $resource = new NamedObject;

        $adjuster->shouldReceive('process')
                 ->with($input, $resource, null)
                 ->once()
                 ->andReturn($adjusted);

        $validatorFactory->shouldReceive('create')
            ->with($rules, NamedObject::class)
            ->andReturn($validator);

        $validator->shouldReceive('validate')
                  ->with($adjusted, $resource, null)
                  ->once()
                  ->andReturn($input);

        $caster->shouldReceive('process')
               ->with($adjusted, $resource, null)
               ->once()
               ->andReturn($casted);

        $awaited = $normalizer->adjust()
                              ->validate($rules)
                              ->cast()
                              ->normalize($input, $resource);

        $this->assertEquals($awaited, $casted);
    }

    public function test_normalize_with_adjust_validate_cast_and_explicit_validation_disabled()
    {
        $adjuster = $this->mock(InputProcessorContract::class);
        $validatorFactory = $this->mock(ValidatorFactory::class);
        $caster = $this->mock(InputProcessorContract::class);

        $normalizer = $this->newNormalizer($validatorFactory, $adjuster, $caster);

        $input = ['a' => 1, 'b' => 2];
        $adjusted = ['a' => 1, 'b' => 2, 'c' => 3];
        $casted = ['a' => 1];
        $rules = ['a' => 'min:2'];

        $resource = new NamedObject;

        $adjuster->shouldReceive('process')
                 ->with($input, $resource, null)
                 ->once()
                 ->andReturn($adjusted);

        $validatorFactory->shouldReceive('make')
                         ->never();

        $caster->shouldReceive('process')
               ->with($adjusted, $resource, null)
               ->once()
               ->andReturn($casted);

        $awaited = $normalizer->adjust()
                              ->validate(false)
                              ->cast()
                              ->normalize($input, $resource);

        $this->assertEquals($awaited, $casted);
    }

    public function test_normalize_with_adjust_validate_cast_with_custom_validator()
    {
        $adjuster = $this->mock(InputProcessorContract::class);
        $validator = $this->mock(Validator::class);
        $validatorFactory = $this->mock(ValidatorFactory::class);
        $caster = $this->mock(InputProcessorContract::class);

        $normalizer = $this->newNormalizer($validatorFactory, $adjuster, $caster);

        $customValidator = $this->mock(Validator::class);

        $input = ['a' => 1, 'b' => 2];
        $adjusted = ['a' => 1, 'b' => 2, 'c' => 3];
        $casted = ['a' => 1];
        $rules = ['a' => 'min:2'];

        $resource = new NamedObject;

        $adjuster->shouldReceive('process')
                 ->with($input, $resource, null)
                 ->once()
                 ->andReturn($adjusted);

        $validatorFactory->shouldReceive('make')
                         ->with([], $resource)
                         ->andReturn($validator);

        $validator->shouldReceive('validate')
                  ->never();

        $customValidator->shouldReceive('validate')
                  ->with($adjusted, $resource, null)
                  ->once()
                  ->andReturn($input);

        $caster->shouldReceive('process')
               ->with($adjusted, $resource, null)
               ->once()
               ->andReturn($casted);

        $awaited = $normalizer->adjust()
                              ->validate($customValidator)
                              ->cast()
                              ->normalize($input, $resource);

        $this->assertEquals($awaited, $casted);
    }

    /**
     * @expectedException Ems\Contracts\Core\Errors\Unsupported
     **/
    public function test_normalize_with_unsupported_validation_parameter_throws_exception()
    {
        $adjuster = $this->mock(InputProcessorContract::class);
        $validator = $this->mock(Validator::class);
        $validatorFactory = $this->mock(ValidatorFactory::class);
        $caster = $this->mock(InputProcessorContract::class);

        $normalizer = $this->newNormalizer($validatorFactory, $adjuster, $caster);

        $customValidator = $this->mock(Validator::class);

        $input = ['a' => 1, 'b' => 2];
        $adjusted = ['a' => 1, 'b' => 2, 'c' => 3];
        $casted = ['a' => 1];
        $rules = ['a' => 'min:2'];

        $resource = new NamedObject;

        $awaited = $normalizer->adjust()
                              ->validate(15)
                              ->cast()
                              ->normalize($input, $resource);

        $this->assertEquals($awaited, $casted);
    }

    public function test_normalize_without_cast_if_explicit_disabled()
    {
        $adjuster = $this->mock(InputProcessorContract::class);
        $validator = $this->mock(Validator::class);
        $validatorFactory = $this->mock(ValidatorFactory::class);
        $caster = $this->mock(InputProcessorContract::class);

        $normalizer = $this->newNormalizer($validatorFactory, $adjuster, $caster);

        $input = ['a' => 1, 'b' => 2];
        $adjusted = ['a' => 1, 'b' => 2, 'c' => 3];
        $casted = ['a' => 1];
        $rules = ['a' => 'min:2'];

        $resource = new NamedObject;

        $adjuster->shouldReceive('process')
                 ->with($input, $resource, null)
                 ->once()
                 ->andReturn($adjusted);

        $validatorFactory->shouldReceive('create')
                         ->with($rules, NamedObject::class)
                         ->andReturn($validator);

        $validator->shouldReceive('validate')
                  ->with($adjusted, $resource, null)
                  ->once()
                  ->andReturn($input);

        $caster->shouldReceive('process')
               ->never();

        $awaited = $normalizer->validate($rules)
                              ->adjust()
                              ->cast(false)
                              ->normalize($input, $resource);

        $this->assertEquals($awaited, $adjusted);
    }

    public function test_normalize_with_custom_caster()
    {
        $adjuster = $this->mock(InputProcessorContract::class);
        $validator = $this->mock(Validator::class);
        $validatorFactory = $this->mock(ValidatorFactory::class);
        $caster = $this->mock(InputProcessorContract::class);

        $normalizer = $this->newNormalizer($validatorFactory, $adjuster, $caster);

        $input = ['a' => 1, 'b' => 2];
        $adjusted = ['a' => 1, 'b' => 2, 'c' => 3];
        $casted = ['a' => 1];
        $rules = ['a' => 'min:2'];

        $resource = new NamedObject;

        $customCaster = $this->mock(InputProcessorContract::class);


        $adjuster->shouldReceive('process')
                 ->with($input, $resource, null)
                 ->once()
                 ->andReturn($adjusted);


        $validatorFactory->shouldReceive('create')
                         ->with($rules, NamedObject::class)
                         ->andReturn($validator);

        $validator->shouldReceive('validate')
                  ->with($adjusted, $resource, null)
                  ->once()
                  ->andReturn($input);

        $customCaster->shouldReceive('process')
               ->with($adjusted, $resource, null)
               ->once()
               ->andReturn($casted);

        $caster->shouldReceive('process')->never();

        $awaited = $normalizer->adjust()
                              ->validate($rules)
                              ->cast($customCaster)
                              ->normalize($input, $resource);

        $this->assertEquals($awaited, $casted);
    }

    public function test_normalize_with_modified_cast_chain()
    {
        $adjuster = $this->mock(InputProcessorContract::class);


        $validator = $this->mock(Validator::class);
        $validatorFactory = $this->mock(ValidatorFactory::class);
        $caster = new InputProcessor;

        $normalizer = $this->newNormalizer($validatorFactory, $adjuster, $caster);

        $input = ['a' => 1, 'b' => 2];
        $adjusted = ['a' => 1, 'b' => 2, 'c' => 3];
        $casted = ['a' => 1];
        $rules = ['a' => 'min:2'];

        $resource = new NamedObject;


        $caster->extend('foo', function () use ($casted) {
            return $casted;
        });

        $adjuster->shouldReceive('process')
                 ->with($input, $resource, null)
                 ->once()
                 ->andReturn($adjusted);

        $validatorFactory->shouldReceive('create')
                         ->with($rules, NamedObject::class)
                         ->andReturn($validator);

        $validator->shouldReceive('validate')
                  ->with($adjusted, $resource, null)
                  ->once()
                  ->andReturn($input);

        $awaited = $normalizer->adjust()
                              ->validate($rules)
                              ->cast('foo')
                              ->normalize($input, $resource);

        $this->assertEquals($awaited, $casted);
    }

    public function test_normalize_with_adjust_validate_calls_listeners()
    {
        $adjuster = $this->mock(InputProcessorContract::class);
        $validator = $this->mock(Validator::class);
        $validatorFactory = $this->mock(ValidatorFactory::class);
        $caster = $this->mock(InputProcessorContract::class);

        $normalizer = $this->newNormalizer($validatorFactory, $adjuster, $caster);

        $input = ['a' => 1, 'b' => 2];
        $adjusted = ['a' => 1, 'b' => 2, 'c' => 3];
        $casted = ['a' => 1];

        $resource = new NamedObject;

        $adjuster->shouldReceive('process')
                 ->with($input, $resource, null)
                 ->once()
                 ->andReturn($adjusted);

        $validatorFactory->shouldReceive('create')
                         ->with([], NamedObject::class)
                         ->andReturn($validator);

        $validator->shouldReceive('validate')
                  ->with($adjusted, $resource, null)
                  ->once()
                  ->andReturn($input);

        $caster->shouldReceive('process')
               ->with($adjusted, $resource, null)
               ->once()
               ->andReturn($casted);

        $handler = function ($input) {
            return $input;
        };

        $listeners = [
            'adjust.before'   => new LoggingCallable($handler),
            'adjust.after'    => new LoggingCallable($handler),
            'validate.before' => new LoggingCallable($handler),
            'validate.after'  => new LoggingCallable($handler),
            'cast.before'     => new LoggingCallable($handler),
            'cast.after'      => new LoggingCallable($handler),
        ];

        foreach ($listeners as $event=>$listener) {
            list($hook, $position) = explode('.', $event);
            $method = "on" . ucfirst($position);
            $normalizer->$method($hook, $listener);
        }

        $awaited = $normalizer->normalize($input, $resource);

        $this->assertEquals($awaited, $casted);

        foreach ($listeners as $event=>$listener) {
            $this->assertCount(1, $listener);
        }

        $this->assertEquals($input, $listeners['adjust.before']->arg(0));
        $this->assertEquals($adjusted, $listeners['adjust.after']->arg(0));
        $this->assertEquals($adjusted, $listeners['validate.before']->arg(0));
        $this->assertEquals($adjusted, $listeners['validate.after']->arg(0));
        $this->assertEquals($adjusted, $listeners['cast.before']->arg(0));
        $this->assertEquals($casted, $listeners['cast.after']->arg(0));
    }

    /**
     * @expectedException UnexpectedValueException
     **/
    public function test_normalize_with_listeners_which_dont_return_array_throws_exception()
    {
        $adjuster = $this->mock(InputProcessorContract::class);
        $validator = $this->mock(Validator::class);
        $validatorFactory = $this->mock(ValidatorFactory::class);
        $caster = $this->mock(InputProcessorContract::class);

        $normalizer = $this->newNormalizer($validatorFactory, $adjuster, $caster);

        $input = ['a' => 1, 'b' => 2];

        $resource = new NamedObject;


        $normalizer->onBefore('adjust', function () {}); //Does not return input

        $awaited = $normalizer->normalize($input, $resource);

    }

    protected function newNormalizer(ValidatorFactory $factory=null, InputProcessorContract $adjuster=null, InputProcessorContract $caster=null, $validator=null)
    {
        $factory = $factory ?: new \Ems\Validation\ValidatorFactory();
        $adjuster = $adjuster ?: new InputProcessor;
        $caster = $caster?: new InputProcessor;
        return new InputNormalizer($factory, $adjuster, $caster);
    }
}
