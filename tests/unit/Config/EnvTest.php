<?php
/**
 *  * Created by mtils on 3/21/21 at 8:05 AM.
 **/

namespace Ems\Config;


use ArrayAccess;
use Closure;
use Ems\Config\Exception\EnvFileException;
use Ems\TestCase;
use Ems\TestData;
use Ems\Testing\Cheat;
use OutOfBoundsException;
use PHPUnit\Framework\Attributes\Test;
use Traversable;

use function file_get_contents;
use function fopen;
use function getenv;
use function key;
use function putenv;

class EnvTest extends TestCase
{
    use TestData;

    #[Test] public function it_instantiates_and_implements_interfaces()
    {
        $instance = $this->make();
        $this->assertInstanceOf(Env::class, $instance);
        $this->assertInstanceOf(ArrayAccess::class, $instance);
        $this->assertInstanceOf(Traversable::class, $instance);
    }

    #[Test] public function load_sample_file()
    {
        $instance = $this->make();
        $file = $this->dataFile('config/simple.env');
        $config = $instance->load($file);
        $this->assertCount(9, $config);
        $this->assertEquals('bar', $config['FOO']);
        $this->assertEquals('with spaces', $config['SPACED']);
        $this->assertEquals('a value with a # character', $config['QUOTED']);
        $this->assertEquals('a value with a # character & a quote " character inside quotes', $config['QUOTESWITHQUOTEDCONTENT']);
        $this->assertSame('', $config['EMPTY']);
        $this->assertSame('', $config['EMPTY2']);
        $this->assertSame('foo', $config['FOOO']);
        $this->assertSame(true, $config['BOOLEAN']);
        $this->assertSame('', $config['CNULL']);
    }

    #[Test] public function load_sample_file_by_resource()
    {
        $instance = $this->make();
        $file = fopen($this->dataFile('config/simple.env'), 'r');
        $config = $instance->load($file);
        $this->assertCount(9, $config);
        $this->assertEquals('bar', $config['FOO']);
        $this->assertEquals('with spaces', $config['SPACED']);
        $this->assertEquals('a value with a # character', $config['QUOTED']);
        $this->assertEquals('a value with a # character & a quote " character inside quotes', $config['QUOTESWITHQUOTEDCONTENT']);
        $this->assertSame('', $config['EMPTY']);
        $this->assertSame('', $config['EMPTY2']);
        $this->assertSame('foo', $config['FOOO']);
        $this->assertSame(true, $config['BOOLEAN']);
        $this->assertSame('', $config['CNULL']);
    }

    #[Test] public function load_sample_file_by_string()
    {
        $instance = $this->make();
        $file = file_get_contents($this->dataFile('config/simple.env'));
        $config = $instance->load($file);
        $this->assertCount(9, $config);
        $this->assertEquals('bar', $config['FOO']);
        $this->assertEquals('with spaces', $config['SPACED']);
        $this->assertEquals('a value with a # character', $config['QUOTED']);
        $this->assertEquals('a value with a # character & a quote " character inside quotes', $config['QUOTESWITHQUOTEDCONTENT']);
        $this->assertSame('', $config['EMPTY']);
        $this->assertSame('', $config['EMPTY2']);
        $this->assertSame('foo', $config['FOOO']);
        $this->assertSame(true, $config['BOOLEAN']);
        $this->assertSame('', $config['CNULL']);
    }

    #[Test] public function load_casted_file()
    {
        $instance = $this->make();

        $file = fopen($this->dataFile('config/casting.env'), 'r');
        $config = $instance->load($file);
        $this->assertCount(41, $config);
        $this->assertSame(0, $config['ZERO']);
        $this->assertSame(1, $config['ONE']);
        $this->assertSame(2, $config['TWO']);
        $this->assertSame(5.34, $config['DECIMAL']);
        $this->assertSame(-23655.89, $config['NEGATIVE_DECIMAL']);
        $this->assertSame(99999999, $config['BIG']);
        $this->assertSame('something', $config['SOMETHING']);
        $this->assertSame('', $config['EMPTY']);
        $this->assertSame('', $config['EMPTY_STRING']);
        $this->assertSame(null, $config['NULL_STRING']);
        $this->assertSame(-2, $config['NEGATIVE']);
        $this->assertSame('-', $config['MINUS']);
        $this->assertSame('~', $config['TILDA']);
        $this->assertSame('!', $config['EXCLAMATION']);
        $this->assertSame('123', $config['QUOTED']);

        $this->assertSame(true, $config['LOWERCASE_TRUE']);
        $this->assertSame(false, $config['LOWERCASE_FALSE']);
        $this->assertSame(true, $config['UPPERCASE_TRUE']);
        $this->assertSame(false, $config['UPPERCASE_FALSE']);
        $this->assertSame(true, $config['MIXEDCASE_TRUE']);
        $this->assertSame(false, $config['MIXEDCASE_FALSE']);
        $this->assertSame("true", $config['QUOTED_TRUE']);
        $this->assertSame("false", $config['QUOTED_FALSE']);

        $this->assertSame(1, $config['NUMBER_TRUE']);
        $this->assertSame(0, $config['NUMBER_FALSE']);

        $this->assertSame(true, $config['LOWERCASE_ON']);
        $this->assertSame(false, $config['LOWERCASE_OFF']);
        $this->assertSame(true, $config['UPPERCASE_ON']);
        $this->assertSame(false, $config['UPPERCASE_OFF']);
        $this->assertSame(true, $config['MIXEDCASE_ON']);
        $this->assertSame(false, $config['MIXEDCASE_OFF']);
        $this->assertSame("on", $config['QUOTED_ON']);
        $this->assertSame("off", $config['QUOTED_OFF']);

        $this->assertSame(true, $config['LOWERCASE_YES']);
        $this->assertSame(false, $config['LOWERCASE_NO']);
        $this->assertSame(true, $config['UPPERCASE_YES']);
        $this->assertSame(false, $config['UPPERCASE_NO']);
        $this->assertSame(true, $config['MIXEDCASE_YES']);
        $this->assertSame(false, $config['MIXEDCASE_NO']);
        $this->assertSame("yes", $config['QUOTED_YES']);
        $this->assertSame("no", $config['QUOTED_NO']);

    }

    #[Test] public function it_sets_values_in_env()
    {
        $this->assertFalse(isset($_ENV['foo']));
        Env::set('foo', 'bar');
        $this->assertEquals('bar', $_ENV['foo']);
    }

    #[Test] public function it_sets_values_in_server()
    {
        $this->assertFalse(isset($_SERVER['foo2']));
        Env::set('foo2', 'bar');
        $this->assertEquals('bar', $_SERVER['foo2']);
    }

    #[Test] public function it_sets_values_in_environment()
    {
        $this->assertFalse(getenv('foo3'));
        Env::set('foo3', 'bar');
        $this->assertEquals('bar', getenv('foo3'));
    }

    #[Test] public function get_returns_from_env_array()
    {
        $var = 'some_special_var';
        $value = 'spongebob';
        $this->assertFalse(isset($_ENV[$var]));
        $this->assertNull(Env::get($var));
        $_ENV[$var] = $value;
        $this->assertEquals($value, Env::get($var));
    }

    #[Test] public function get_returns_from_server_array()
    {
        $var = 'some_special_var2';
        $value = 'patrick';
        $this->assertFalse(isset($_SERVER[$var]));
        $this->assertNull(Env::get($var));
        $_SERVER[$var] = $value;
        $this->assertEquals($value, Env::get($var));
    }

    #[Test] public function get_returns_from_environment()
    {
        $var = 'some_special_var3';
        $value = 'sandy';
        $this->assertFalse(getenv($var));
        $this->assertNull(Env::get($var));
        $_SERVER[$var] = $value;
        putenv("$var=$value");
        $this->assertEquals($value, Env::get($var));
    }

    #[Test] public function get_returns_forced_from_environment()
    {
        $var = 'PATH';
        $value = getenv($var);
        $this->assertEquals($value, Env::get($var, true));
    }

    #[Test] public function clear_removes_variable()
    {
        $var = 'bull';
        $val = 'green';
        $this->assertNull(Env::get($var));
        $this->assertFalse(isset($_ENV[$var]));
        Env::set($var, 'green');
        $this->assertEquals($val, Env::get($var));
        $this->assertTrue(isset($_ENV[$var]));
        Env::clear($var);
        $this->assertNull(Env::get($var));
        $this->assertFalse(isset($_ENV[$var]));

    }

    #[Test] public function getSetter_returns_setter()
    {
        $this->assertInstanceOf(Closure::class, Env::getSetter(Env::SERVER_ARRAY_SETTER));
    }

    #[Test] public function getSetter_throws_Exception_if_setter_not_found()
    {
        $this->expectException(OutOfBoundsException::class);
        $this->assertInstanceOf(Closure::class, Env::getSetter('foo'));
    }

    #[Test] public function setSetter_sets_and_uses_custom_setter()
    {
        $testArray = [];
        $setter = function ($name, $value) use (&$testArray) {
            $testArray[$name] = $value;
        };
        Env::setSetter('custom', $setter);
        $sequence = Env::getSetterSequence();
        $sequence[] = 'custom';
        Env::setSetterSequence($sequence);

        $check = [
            'MY_NEW_VAR' => 'MY_NEW_VALUE'
        ];
        Env::set(key($check), $check[key($check)]);

        $this->assertEquals($check, $testArray);

    }

    #[Test] public function unquoted_values_with_spaces_throw_exception()
    {
        $test = 'THE_NEW_VALUE=some words with spaces';

        try {
            $this->make()->load($test);
            $this->fail("The test '$test' must lead to an fatal error");
        } catch (EnvFileException $e) {
            $this->assertEquals(1, $e->getEnvFileLine());
            $this->assertStringContainsString('in line', $e->getMessage());
            $this->assertStringContainsString('surrounded by spaces', $e->getMessage());
        }
    }

    #[Test] public function escaped_values_with_spaces_throw_exception()
    {
        $test = 'THE_NEW_VALUE="some\ words\ with\ spaces"';
        $config = $this->make()->load($test);
        $this->assertEquals('some\ words\ with\ spaces',$config['THE_NEW_VALUE']);
    }

    #[Test] public function ArrayAccess_works()
    {
        $instance = $this->make();
        $file = $this->dataFile('config/simple.env');
        $config = $instance->load($file);
        $this->assertCount(9, $config);

        $this->assertEquals('bar', $instance['FOO']);
        $this->assertFalse(isset($instance['bean']));
        $instance['bean'] = 'tasty';
        $this->assertTrue(isset($instance['bean']));
        $this->assertEquals('tasty', $_ENV['bean']);

        unset($instance['bean']);
        $this->assertFalse(isset($instance['bean']));
        $this->assertFalse(isset($_ENV['bean']));

    }

    #[Test] public function toArray_returns_all()
    {
        $instance = $this->make();
        $file = $this->dataFile('config/simple.env');
        $config = $instance->load($file);
        $this->assertCount(9, $config);
        $array = $instance->toArray();
        $this->assertEquals('bar', $array['FOO']);
        $this->assertEquals('with spaces', $array['SPACED']);
        $this->assertEquals('a value with a # character', $array['QUOTED']);
        $this->assertEquals('a value with a # character & a quote " character inside quotes', $array['QUOTESWITHQUOTEDCONTENT']);
        $this->assertSame('', $array['EMPTY']);
        $this->assertSame('', $array['EMPTY2']);
        $this->assertSame('foo', $array['FOOO']);
        $this->assertSame(true, $array['BOOLEAN']);
        $this->assertSame('', $array['CNULL']);
    }

    #[Test] public function getIterator_iterates_over_values()
    {
        $instance = $this->make();
        $file = $this->dataFile('config/simple.env');
        $config = $instance->load($file);
        $this->assertCount(9, $config);
        $array = [];
        foreach ($instance as $key=>$value) {
            $array[$key] = $value;
        }
        $this->assertEquals('bar', $array['FOO']);
        $this->assertEquals('with spaces', $array['SPACED']);
        $this->assertEquals('a value with a # character', $array['QUOTED']);
        $this->assertEquals('a value with a # character & a quote " character inside quotes', $array['QUOTESWITHQUOTEDCONTENT']);
        $this->assertSame('', $array['EMPTY']);
        $this->assertSame('', $array['EMPTY2']);
        $this->assertSame('foo', $array['FOOO']);
        $this->assertSame(true, $array['BOOLEAN']);
        $this->assertSame('', $array['CNULL']);
    }

    #[Test] public function split_without_mb_str_split()
    {
        $instance = $this->make();
        Cheat::set($instance, 'hideMbStrSplit', true);
        $file = $this->dataFile('config/simple.env');
        $config = $instance->load($file);
        $this->assertCount(9, $config);
        $array = $instance->toArray();
        $this->assertEquals('bar', $array['FOO']);
        $this->assertEquals('with spaces', $array['SPACED']);
        $this->assertEquals('a value with a # character', $array['QUOTED']);
        $this->assertEquals('a value with a # character & a quote " character inside quotes', $array['QUOTESWITHQUOTEDCONTENT']);
        $this->assertSame('', $array['EMPTY']);
        $this->assertSame('', $array['EMPTY2']);
        $this->assertSame('foo', $array['FOOO']);
        $this->assertSame(true, $array['BOOLEAN']);
        $this->assertSame('', $array['CNULL']);
    }

    protected function make()
    {
        return new Env();
    }
}