<?php


namespace Ems\Validation\Illuminate;

use Ems\Contracts\Validation\ValidationConverter as ConverterContract;
use Ems\Contracts\Validation\ValidatorFactory as ValidatorFactoryContract;
use Ems\Contracts\Core\TextProvider as TextProviderContract;
// use Ems\Contracts\Core;
use Illuminate\Validation\Factory as IlluminateFactory;
use Illuminate\Contracts\Translation\Translator as TranslatorContract;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\LoaderInterface;
use Illuminate\Translation\Translator;
use Ems\Testing\LoggingCallable;
use Ems\Core\Helper;
use Ems\Core\NamedObject;
use Ems\Core\Laravel\TranslatorTextProvider;
use Ems\Contracts\Core\AppliesToResource;

/**
 * @group validation
 **/
class ValidationFactoryTest extends \Ems\TestCase
{
    public function test_implements_interface()
    {
        $this->assertInstanceOf(
            ValidatorFactoryContract::class,
            $this->newFactory()
        );
    }

    public function test_make_returns_validator_with_passed_rules()
    {
        $factory = $this->newFactory();

        $rules = ['login' => 'required|min:2|max:255'];

        $parsed = [
            'login' => [
                'required' => [],
                'min'      => ['2'],
                'max'      => ['255']
            ]
        ];

        $validator = $factory->make($rules);
        $this->assertInstanceOf(GenericValidator::class, $validator);
        $this->assertEquals($parsed, $validator->rules());
    }

    public function test_make_returns_null_with_empty_rules()
    {
        $this->assertNull($this->newFactory()->make([]));
    }

    protected function newFactory()
    {
        return new ValidatorFactory();
    }

}

