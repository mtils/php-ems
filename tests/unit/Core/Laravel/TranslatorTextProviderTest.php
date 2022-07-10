<?php

namespace Ems\Core\Laravel;

use Ems\Contracts\Core\Multilingual;
use Ems\Contracts\Core\TextProvider;
use Illuminate\Contracts\Translation\Loader;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\LoaderInterface;
use Illuminate\Translation\Translator;

use function interface_exists;
use function is_array;

class TranslatorTextProviderTest extends \Ems\TestCase
{

    public function test_implements_interfaces()
    {
        $provider = $this->newProvider();
        $this->assertInstanceof(TextProvider::class, $provider);
        $this->assertInstanceof(Multilingual::class, $provider);
    }

    public function test_has_return_true_if_key_found()
    {
        $this->assertTrue($this->newProvider()->has('root.confirmed'));
    }

    public function test_has_return_false_if_key_found()
    {
        $this->assertFalse($this->newProvider()->has('root.foo'));
    }

    public function test_get_returns_translated_string()
    {
        $provider = $this->newProvider(['foo' => 'bar']);
        $this->assertEquals('bar', $provider->get('root.foo'));
    }

    public function test_get_returns_key_if_translation_not_found()
    {
        $provider = $this->newProvider();
        $this->assertEquals('root.foo', $provider->get('root.foo'));
    }

    public function test_get_returns_replaced_string()
    {
        $provider = $this->newProvider(['foo' => 'bar :key foo']);
        $this->assertEquals('bar acme foo', $provider->get('root.foo', ['key'=>'acme']));
    }

    public function test_choice_returns_tanslation_per_number()
    {
        $provider = $this->newProvider();
        $this->assertEquals('One apple', $provider->choice('root.apple', 1));
        $this->assertEquals('Two apples', $provider->choice('root.apple', 2));
    }

    public function test_setLocale_changes_locale_inline()
    {

        $loader = $this->newLoader(['foo' => 'bar']);
        $loader->addMessages('de', 'root', ['foo'=>'bär']);
        $provider = $this->newProvider($loader);
        $this->assertEquals('bar', $provider->get('root.foo'));
        $this->assertEquals('en', $provider->getLocale());
        $this->assertSame($provider, $provider->setLocale('de'));
        $this->assertEquals('de', $provider->getLocale());
        $this->assertEquals('bär', $provider->get('root.foo'));
    }

    public function test_replicated_returns_translated_string_by_offset_key()
    {
        $baseProvider = $this->newProvider(['foo' => 'bar']);
        $provider = $baseProvider->forDomain('root');

        $this->assertNotSame($provider, $baseProvider);
        $this->assertEquals('root', $provider->getDomain());
        $this->assertEquals('bar', $provider->get('foo'));
    }

    public function test_forLocale_changes_locale_of_fork()
    {
        $loader = $this->newLoader(['foo' => 'bar']);
        $loader->addMessages('de', 'root', ['foo'=>'bär']);
        $enProvider = $this->newProvider($loader);
        $deProvider = $enProvider->forLocale('de');
        $this->assertNotSame($deProvider, $enProvider);
        $this->assertEquals('bar', $enProvider->get('root.foo'));
        $this->assertEquals('en', $enProvider->getLocale());
        $this->assertEquals('de', $deProvider->getLocale());
        $this->assertEquals('bär', $deProvider->get('root.foo'));
    }

    public function test_get_returns_translated_string_by_namespaced_key()
    {
        $loader = $this->newLoader([]);
        $provider = $this->newProvider($loader, '');
        $loader->addMessages('en', 'root', ['foo'=>'bar'], 'acme');
        $this->assertEquals('bar', $provider->get('acme::root.foo'));

        $fork = $provider->forNamespace('acme');
        $this->assertNotSame($fork, $provider);
        $this->assertEquals('acme', $fork->getNamespace());
        $this->assertEquals('bar', $fork->get('root.foo'));
    }

    protected function newProvider($translator=null, $domain = '', $namespace = '')
    {
        $translator = $translator instanceof Translator ? $translator : $this->newTranslator($translator);
        return new TranslatorTextProvider($translator, $domain, $namespace);
    }

    protected function newTranslator($messages=null)
    {
        $isLoader = false;
        if (interface_exists(LoaderInterface::class) && $messages instanceof LoaderInterface) {
            $isLoader = true;
        }
        if (!$isLoader && interface_exists(Loader::class) && $messages instanceof Loader) {
            $isLoader = true;
        }
        $loader = $isLoader ? $messages : $this->newLoader(is_array($messages) ? $messages : null);
        return new Translator($loader, 'en');
    }

    protected function newLoader($messages = null)
    {
        $loader = new ArrayLoader();
        $loader->addMessages('en', 'root', $messages ?: $this->sampleMessages());
        return $loader;
    }

    protected function sampleMessages()
    {
        return [
            "confirmed"            => "The :attribute confirmation does not match.",
            "date"                 => "The :attribute is not a valid date.",
            "date_format"          => "The :attribute does not match the format :format.",
            "different"            => "The :attribute and :other must be different.",
            "digits"               => "The :attribute must be :digits digits.",
            "digits_between"       => "The :attribute must be between :min and :max digits.",
            "email"                => "The :attribute must be a valid email address.",
            "exists"               => "The selected :attribute is invalid.",
            "apple"                => "One apple|Two apples"
        ];
    }
}
