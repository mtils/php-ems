<?php


namespace Ems\Foundation;

use Ems\Core\NamedObject;
use Ems\Validation\Validator;
use Ems\Validation\ValidatorFactory;

class SelfNormalizingTraitTest extends \Ems\TestCase
{

    public function test_add_to_normalizer()
    {
        $normalizer = $this->newNormalizer();
        $obj = $this->newSelfNormalizer();
        $obj->setNormalizer($normalizer);

        $input = [
            'a' => 'b',
            'c' => 'd'
        ];

        $awaited = [];

        $this->assertEquals($input, $normalizer->normalize($input));
        $this->assertEquals([], $obj->calls);

    }

    public function test_call_simple_key_method()
    {
        $normalizer = $this->newNormalizer();
        $obj = $this->newSelfNormalizer();
        $obj->setNormalizer($normalizer);

        $input = [
            'foo' => 'b',
            'c'   => 'd'
        ];

        $awaited = [
            'foo' => 'bar',
            'c'   => 'd'
        ];

        $args = [
            'b',
            $input,
            null,
            null
        ];

        $this->assertEquals($awaited, $normalizer->normalize($input));
        $this->assertEquals('adjustFoo', $obj->calls[0][0]);
        $this->assertEquals($args, $obj->calls[0][1]);

    }

    public function test_call_snake_case_key_methods()
    {

        $normalizer = $this->newNormalizer();
        $obj = $this->newSelfNormalizer();
        $obj->setNormalizer($normalizer);

        $input = [
            'foo' => 'b',
            'c'   => 'd',
            'uncle_benz' => 'hihi'
        ];

        $awaited = [
            'foo' => 'bar',
            'c'   => 'd',
            'uncle_benz' => 'hihi'
        ];

        $args = [
            'b',
            $input,
            null,
            null
        ];

        $validatorArgs = [
            'hihi'
        ];
        $this->assertEquals($awaited, $normalizer->normalize($input));

        $this->assertEquals('adjustFoo', $obj->calls[0][0]);
        $this->assertEquals($args, $obj->calls[0][1]);

        $this->assertEquals('validateUncleBenz', $obj->calls[1][0]);
        $this->assertEquals(['hihi', $awaited, null, []], $obj->calls[1][1]);

    }

    public function test_call_pointed_key_methods()
    {
        $normalizer = $this->newNormalizer();
        $obj = $this->newSelfNormalizer();
        $obj->setNormalizer($normalizer);

        $resource = new NamedObject;
        $locale = 'hu';

        $input = [
            'foo' => 'b',
            'c'   => 'd',
            'uncle.benz' => 'hihi',
            'address.street' => 'Str.'
        ];

        $validatorInput = [
            'foo' => 'bar',
            'c'   => 'd',
            'uncle.benz' => 'hihi',
            'address.street' => 'Str.'
        ];

        $awaited = [
            'foo' => 'bar',
            'c'   => 'd',
            'uncle.benz' => 'hihi',
            'address.street' => 'Elm Str.'
        ];

        $adjustArgs = [
            'b',
            $input,
            $resource,
            $locale
        ];

        $castArgs = [
            'b',
            $input,
            $resource,
            $locale
        ];

        $this->assertEquals($awaited, $normalizer->normalize($input, $resource, $locale));

        $this->assertEquals('adjustFoo', $obj->calls[0][0]);
        $this->assertEquals($adjustArgs, $obj->calls[0][1]);

        $this->assertEquals('validateUncleBenz', $obj->calls[1][0]);
        $this->assertEquals(['hihi', $validatorInput, $resource, []], $obj->calls[1][1]);

        $this->assertEquals('castAddressStreet', $obj->calls[2][0]);
        $this->assertEquals(['Str.', $validatorInput, $resource, $locale], $obj->calls[2][1]);

    }

    protected function newSelfNormalizer()
    {
        return new SelfNormalizingTraitObject;
    }

    protected function newNormalizer()
    {
        return new InputNormalizer($this->newValidatorFactory());
    }

    protected function newValidatorFactory()
    {
        $createValidator = function () {
            return new Validator(['a' => 'b'], '', function ($baseValidator, $input) {
                return $input;
            });
        };
        return new ValidatorFactory($createValidator);
    }
}


class SelfNormalizingTraitObject
{

    use SelfNormalizingTrait;

    /**
     * @var array
     **/
    public $calls = [];

    public function setNormalizer($normalizer)
    {
        $this->addMeToNormalizer($normalizer);
    }

    protected function adjustFoo($value, array $input, $resource, $locale=null)
    {
        $this->calls[] = [
            __FUNCTION__,
            func_get_args()
        ];

        return 'bar';

    }

    protected function validateUncleBenz($value)
    {
        $this->calls[] = [
            __FUNCTION__,
            func_get_args()
        ];
    }

    protected function castAddressStreet($value, $input, $resource)
    {
        $this->calls[] = [
            __FUNCTION__,
            func_get_args()
        ];
        return 'Elm Str.';
    }

}
