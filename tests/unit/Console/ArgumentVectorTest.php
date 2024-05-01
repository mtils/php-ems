<?php
/**
 *  * Created by mtils on 22.09.19 at 05:55.
 **/

namespace Ems\Console;


use Ems\TestCase;
use PHPUnit\Framework\Attributes\Test;

use function array_keys;
use function array_merge;
use function func_get_args;
use function is_array;

class ArgumentVectorTest extends TestCase
{
    #[Test] public function it_instantiates()
    {
        $this->assertInstanceOf(ArgumentVector::class, $this->make());
    }

    #[Test] public function argv_returns_input()
    {
        $input = ['a', 'b', 'c'];
        $this->assertEquals($input, $this->make($input)->argv());
    }

    #[Test] public function command_returns_parsed_command()
    {
        $input = ['console', 'a', 'b', 'c'];
        $this->assertEquals($input[0], $this->make($input)->command());
    }

    #[Test] public function arguments_returns_segments_without_prefix()
    {
        $input = ['a', 'b', 'c'];
        $argv = $this->c($input);
        $this->assertEquals($input, $this->make($argv)->arguments());
    }


    #[Test] public function options_returns_short_options()
    {
        $input = ['a', 'b', 'c', '-fbar', '-e', '80', '-v'];

        $expectedOptions = [
            'f' => 'bar',
            'e' => '80',
            'v' => true
        ];

        $argv = $this->c($input);
        $subject = $this->make($argv);
        $subject->defineShortOption('f', true);
        $subject->defineShortOption('e', true);
        $this->assertEquals($expectedOptions, $subject->options());
        $this->assertEquals(['a', 'b', 'c'], $subject->arguments());

    }

    #[Test] public function options_with_multiple_short_options()
    {
        $input = ['a', 'b', '-fbar', '-e', '80', '-v', 'c'];

        $expectedOptions = [
            'f' => true,
            'b' => true,
            'a' => true,
            'r' => true,
            'e' => '80',
            'v' => true
        ];

        $argv = $this->c($input);
        $subject = $this->make($argv);
//        $subject->defineShortOption('f', true);
        $subject->defineShortOption('e', true);
        $this->assertEquals($expectedOptions, $subject->options());
        $this->assertEquals(['a', 'b', 'c'], $subject->arguments());

    }

    #[Test] public function options_returns_long_options()
    {
        $input = ['a', 'b', 'c', '--foo=bar', '--elm=', '--baz'];

        $expectedOptions = [
            'foo' => 'bar',
            'elm' => '',
            'baz' => true
        ];

        $argv = $this->c($input);
        $subject = $this->make($argv);
        $this->assertEquals($expectedOptions, $subject->options());
        $this->assertEquals(['a', 'b', 'c'], $subject->arguments());

    }

    #[Test] public function double_dash_makes_very_following_an_argument()
    {
        $input = ['a', 'b', 'c', '--foo=bar', '--',  '--elm=',  '--baz', 'd', '-s'];

        $expectedOptions = [
            'foo' => 'bar'
        ];

        $expectedArguments = [
            'a', 'b', 'c', '--elm=', '--baz', 'd', '-s'
        ];

        $argv = $this->c($input);
        $subject = $this->make($argv);
        $this->assertEquals($expectedOptions, $subject->options());
        $this->assertEquals($expectedArguments, $subject->arguments());

    }

    #[Test] public function multiple_same_options_creates_array_value()
    {
        $input = ['a', 'b', 'c', '--foo=bar', '--foo=baz', '--baz', '--foo=zof'];

        $expectedOptions = [
            'foo' => ['bar', 'baz', 'zof'],
            'baz' => true
        ];

        $argv = $this->c($input);
        $subject = $this->make($argv);
        $this->assertEquals($expectedOptions, $subject->options());
        $this->assertEquals(['a', 'b', 'c'], $subject->arguments());

    }

    #[Test] public function multiple_same_short_options_creates_array_value()
    {
        $input = ['a', 'b', 'c', '-fbar', '-f', 'baz', '--baz', '-f', 'zof'];

        $expectedOptions = [
            'f' => ['bar', 'baz', 'zof'],
            'baz' => true
        ];

        $argv = $this->c($input);
        $subject = $this->make($argv);
        $subject->defineShortOption('f', true);
        $this->assertEquals($expectedOptions, $subject->options());
        $this->assertEquals(['a', 'b', 'c'], $subject->arguments());

    }

    #[Test] public function definsShortOption_throw_exception_if_option_has_more_than_one_char()
    {
        $this->expectException(\Ems\Contracts\Core\Errors\Unsupported::class);
        $input = ['a', 'b', 'c', '-fbar', '-f', 'baz', '--baz', '-f', 'zof'];

        $argv = $this->c($input);
        $subject = $this->make($argv);
        $subject->defineShortOption('foo');

    }

    #[Test] public function toString_returns_command_line()
    {
        $input = ['a', 'b', 'c', '-fbar', '-f', 'baz', '--baz', '-f', 'zof'];

        $argv = $this->c($input);
        $subject = $this->make($argv);
        $this->assertEquals('command a b c -fbar -f baz --baz -f zof', (string)$subject);

    }

    #[Test] public function definedShortOptions_returns_defined_short_options()
    {
        $input = ['a', 'b', 'c', '--foo=bar', '--foo=baz', '--baz', '--foo=zof'];

        $options = [
            'f' => false,
            'v' => true,
            'c' => true
        ];

        $argv = $this->c($input);
        $subject = $this->make($argv);
        foreach ($options as $key=>$takesValue) {
            $subject->defineShortOption($key, $takesValue);
        }
        $this->assertEquals(array_keys($options), $subject->definedShortOptions());
    }

    #[Test] public function isShortOptionDefined_returns_true_on_defined_options()
    {
        $input = ['a', 'b', 'c', '--foo=bar', '--foo=baz', '--baz', '--foo=zof'];

        $options = [
            'f' => false,
            'v' => true,
            'c' => true
        ];

        $argv = $this->c($input);
        $subject = $this->make($argv);
        foreach ($options as $key=>$takesValue) {
            $subject->defineShortOption($key, $takesValue);
        }

        foreach ($options as $key=>$value) {
            $this->assertTrue($subject->isShortOptionDefined($key));
        }

        foreach (['F', 'o', '9'] as $key) {
            $this->assertFalse($subject->isShortOptionDefined($key));
        }
    }

    /**
     * @param array $commandLine (optional)
     * @param bool  $removeCommand (default:true)
     *
     * @return ArgumentVector
     */
    protected function make($commandLine=[], $removeCommand=true)
    {
        return new ArgumentVector($commandLine, $removeCommand);
    }

    /**
     * @param string|array $args
     *
     * @return array
     */
    protected function c($args)
    {
        $args = is_array($args) ? $args : func_get_args();
        return array_merge(['command'], $args);
    }
}