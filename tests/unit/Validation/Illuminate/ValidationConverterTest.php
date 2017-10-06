<?php


namespace Ems\Validation\Illuminate;

use Ems\Contracts\Validation\ValidationConverter as ConverterContract;
use Ems\Contracts\Validation\Validator as ValidatorContract;
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
class ValidationConverterTest extends \Ems\TestCase
{
    public function test_implements_interface()
    {
        $this->assertInstanceOf(
            ConverterContract::class,
            $this->newConverter()
        );
    }

    protected function newConverter(TextProviderContract $textProvider=null)
    {
        return new ValidationConverter($textProvider ?: $this->newTextProvider());
    }

    protected function newTextProvider($loader=null)
    {
        $translator = new Translator($loader ?: $this->newLoader(), 'en');
        return new TranslatorTextProvider($translator);
    }

    protected function newLoader()
    {
        return new ArrayLoader();
    }
}
