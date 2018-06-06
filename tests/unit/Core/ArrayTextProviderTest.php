<?php
/**
 *  * Created by mtils on 05.06.18 at 17:42.
 **/

namespace Ems\Core;


use Ems\Contracts\Core\Multilingual;
use Ems\Contracts\Core\TextProvider;
use Ems\TestCase;

class ArrayTextProviderTest extends TestCase
{

    public function test_implements_interfaces()
    {
        $provider = $this->newProvider();
        $this->assertInstanceOf(TextProvider::class, $provider);
        $this->assertInstanceOf(Multilingual::class, $provider);
    }

    public function test_has_returns_false_if_no_data_setted()
    {
        $provider = $this->newProvider();
        $this->assertFalse($provider->has('foo'));
        $provider->setData([
            'en' => [
                'messages' => [
                    'created.success' => 'Success!'
                ]
            ]
        ]);
    }

    public function test_has_returns_right_state()
    {
        $provider = $this->newProvider()->setLocale('en');
        $this->assertFalse($provider->has('foo'));
        $provider->setData($this->sampleData());

        $this->assertTrue($provider->has('messages.created.success'));

        $this->assertFalse($provider->has('messages.created.bar'));
    }

    public function test_get_returns_text()
    {
        $data = $this->sampleData();
        $provider = $this->newProvider($data)->setLocale('en');

        $this->assertEquals($data['en.messages.created.success'], $provider->get('messages.created.success'));
    }

    public function test_get_returns_key_if_text_not_found()
    {
        $data = $this->sampleData();
        $provider = $this->newProvider($data)->setLocale('en');

        $this->assertEquals('messages.created.successi', $provider->get('messages.created.successi'));
    }

    public function test_get_returns_text_from_bet_matching_lang()
    {
        $data = $this->sampleData();
        $provider = $this->newProvider($data)->setLocale('en_US');

        $this->assertEquals($data['en_US.messages.created.success'], $provider->get('messages.created.success'));
        $this->assertEquals($data['en.messages.created.special'], $provider->get('messages.created.special'));
    }

    public function test_get_returns_text_from_bet_matching_lang_with_fallbacks()
    {
        $data = $this->sampleData();
        $provider = $this->newProvider($data)->setLocale('de_DE')->setFallbacks(['en']);

        $this->assertEquals($data['de_DE.messages.created.success'], $provider->get('messages.created.success'));
        $this->assertEquals($data['de.messages.created.special'], $provider->get('messages.created.special'));
        $this->assertEquals($data['en.messages.created.failure'], $provider->get('messages.created.failure'));
    }

    public function test_get_replaces_placeholders()
    {
        $data = $this->sampleData();
        $provider = $this->newProvider($data)->setLocale('en');
        $this->assertEquals($data['en.messages.created.placeholders'], $provider->get('messages.created.placeholders'));
        $this->assertEquals('Hello Tim today is Friday', $provider->get('messages.created.placeholders', ['user' => 'Tim', 'weekday'=>'Friday']));
    }

    public function test_choice_chooses_single_choice()
    {
        $data = $this->sampleData();
        $provider = $this->newProvider($data)->setLocale('en');
        $this->assertEquals($data['en.messages.created.success'], $provider->choice('messages.created.success', 0));
        $this->assertEquals($data['en.messages.created.success'], $provider->choice('messages.created.success', 1));
        $this->assertEquals($data['en.messages.created.success'], $provider->choice('messages.created.success', 20));
    }

    public function test_choice_chooses_two_choices()
    {
        $data = $this->sampleData();
        $provider = $this->newProvider($data)->setLocale('en');
        $this->assertEquals('This is one choice', $provider->choice('messages.created.two-choices', 0));
        $this->assertEquals('This is one choice', $provider->choice('messages.created.two-choices', 1));
        $this->assertEquals('This are many choices', $provider->choice('messages.created.two-choices', 20));
    }

    public function test_choice_chooses_three_choices()
    {
        $data = $this->sampleData();
        $provider = $this->newProvider($data)->setLocale('en');
        $this->assertEquals('There is no choice', $provider->choice('messages.created.three-choices', 0));
        $this->assertEquals('This is one choice', $provider->choice('messages.created.three-choices', 1));
        $this->assertEquals('This are many choices', $provider->choice('messages.created.three-choices', 20));
    }

    public function test_getData_returns_assigned_data()
    {
        $data = $this->sampleData();
        $this->assertEquals($data, $this->newProvider($data)->getData());
    }

    public function test_access_on_different_domain()
    {
        $data = $this->sampleData();
        $provider = $this->newProvider($data)->setLocale('en')->forDomain('messages');

        $this->assertEquals($data['en.messages.created.success'], $provider->get('created.success'));
    }

    public function test_access_on_different_locale()
    {
        $data = $this->sampleData();
        $provider = $this->newProvider($data)->setLocale('en');
        $providerDE = $provider->forLocale('de_DE');

        $this->assertEquals($data['en.messages.created.success'], $provider->get('messages.created.success'));
        $this->assertEquals($data['de_DE.messages.created.success'], $providerDE->get('messages.created.success'));
    }

    protected function newProvider(array $data=[])
    {
        return new ArrayTextProvider($data);
    }

    protected function sampleData()
    {
        return [
            'en.messages.created.success' => 'Success!',
            'en.messages.created.two-choices' => 'This is one choice|This are many choices',
            'en.messages.created.three-choices' => 'There is no choice|This is one choice|This are many choices',
            'en.messages.created.failure' => 'Failure!',
            'en.messages.created.special' => 'Special!',
            'en.messages.created.placeholders' => 'Hello :user today is :weekday',
            'en_US.messages.created.success' => 'American Success',
            'de_DE.messages.created.success' => 'Erfolgreich',
            'de.messages.created.success' => 'Erfolg',
            'de.messages.created.special' => 'Spezial'
        ];
    }

}