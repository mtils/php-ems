<?php


namespace Ems\Validation;

use Ems\Contracts\Core\Extendable;
use Ems\Contracts\Validation\ValidationConverter as ConverterContract;
use Ems\Validation\ValidationException;

class ValidationConverterTest extends \Ems\TestCase
{
    public function test_implements_interface()
    {
        $this->assertInstanceOf(ConverterContract::class, $this->newConverter());
        $this->assertInstanceOf(Extendable::class, $this->newConverter());
    }

    public function test_converter_returns_extensions_result()
    {

        $converter = $this->newConverter();
        $converter->extend('foo', function ($validation) {
            return 'bar';
        });

        $this->assertEquals('bar', $converter->convert($this->newValidation(), 'foo'));
    }

    /**
     * @expectedException \Ems\Core\Exceptions\HandlerNotFoundException
     **/
    public function test_convert_throws_exception_if_extension_not_found()
    {
        $converter = $this->newConverter();
        $converter->convert($this->newValidation(), 'foo');
    }

    protected function newValidation(array $failures = [], array $rules = [], $validatorClass=null)
    {
        return new ValidationException($failures, $rules, $validatorClass);
    }

    protected function newConverter()
    {
        return new ValidationConverter($failures, $rules, $validatorClass);
    }
}
