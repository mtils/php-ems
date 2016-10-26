<?php


namespace Ems\Core;

use Ems\Contracts\Core\TextFormatter as FormatterContract;
use Ems\Contracts\Core\NamedCallableChain as ChainContract;
use Ems\Testing\LoggingCallable;


class TextFormatterTest extends \Ems\TestCase
{

    public function test_implements_interface()
    {
        $this->assertInstanceOf(FormatterContract::class, $this->newFormatter());
        $this->assertInstanceOf(ChainContract::class, $this->newFormatter());
    }

    public function test_call_extension()
    {
        $formatter = $this->newFormatter();

        $input = 'foo';
        $result = 'bar';

        $extension = new LoggingCallable(function($text) use ($result) { return $result; });

        $formatter->extend('foo', $extension);

        $this->assertEquals($result, $formatter->foo($input));
        $this->assertEquals($input, $extension->arg(0));

    }

    public function test_call_method()
    {
        $formatter = $this->newFormatter();

        $input = '<p>Immer &Auml;rger mit Umlauten<br>&Uuml;bel</p>';
        $result = "Immer Ärger mit Umlauten\nÜbel";

        $this->assertEquals($result, $formatter->__call('plain', [$input]));

    }

    public function test_standard_function()
    {
        $formatter = $this->newFormatter();

        $input = ' whitespace ';
        $result = "whitespace";

        $this->assertEquals($result, $formatter->__call('trim', [$input]));

    }

    /**
     * @expectedException \Ems\Core\HandlerNotFoundException
     **/
    public function test_call_throws_HandlerNotFoundException_if_filter_unknown()
    {
        $formatter = $this->newFormatter();

        $input = ' whitespace ';
        $result = "whitespace";

        $this->assertEquals($result, $formatter->__call('trimi', [$input]));

    }

    public function test_chain_of_filters_with_parameters()
    {
        $formatter = $this->newFormatter();

        $input = ' whitespace ';
        $filterInput = 'whitespace';
        $filterOutput = 'result';
        $filterParams = 'a,0,22,x';
        $result = "<em>$filterOutput</em>";

        $extension = new LoggingCallable(function($text) use ($filterOutput) { return $filterOutput; });
        $formatter->extend('foo', $extension);

        $this->assertEquals($result, $formatter->format($input, "trim|foo:$filterParams|tag:em"));

        $this->assertEquals('whitespace', $extension->arg(0));
        $this->assertEquals('a', $extension->arg(1));
        $this->assertEquals('0', $extension->arg(2));
        $this->assertEquals('22', $extension->arg(3));
        $this->assertEquals('x', $extension->arg(4));
    }

    protected function newFormatter()
    {
        return new TextFormatter;
    }

}
