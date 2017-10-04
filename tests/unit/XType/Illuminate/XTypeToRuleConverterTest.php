<?php

namespace Ems\XType\Illuminate;

use Ems\Contracts\Core\Extendable;
use Ems\XType\TypeFactory;
use Ems\XType\Aliases;

class XTypeToRuleConverterTest extends \Ems\TestCase
{

    protected $typeFactory;

    public function test_implements_interface()
    {
        $this->assertInstanceOf(Extendable::class, $this->newConverter());
    }

    public function test_convert_flat_type()
    {
        $typeConfig = [
            'min_key'   => 'int|min:3',
            'max_key'   => 'float|max:5|not_null',
            'activated' => 'bool',
            'mobile'    => 'phone_number|not_null',
            'age'       => 'int|min:1|max:160'
        ];

        $type = $this->buildType($typeConfig);

        $awaited = [
            'min_key'   => 'numeric|min:3',
            'max_key'   => 'required|numeric|max:5',
            'activated' => 'boolean',
            'mobile'    => 'required|string|min:3|max:32',
            'age'       => 'numeric|min:1|max:160'
        ];

        $this->assertEquals($awaited, $this->newConverter()->toRule($type));

    }

    public function test_convert_nested_type()
    {

        $userTypeConfig = [
            'min_key'   => 'int|min:3',
            'max_key'   => 'float|max:5|not_null',
            'activated' => 'bool',
            'mobile'    => 'phone_number|not_null',
            'age'       => 'int|min:1|max:160'
        ];

        $addressTypeConfig = [
            'street'    => 'street',
            'zip'       => 'postcode|not_null',
            'location'  => 'location|not_null',
            'house_number' => 'house_number',
            'country_code' => 'country_code2'
        ];

        $type = $this->buildType($userTypeConfig);
        $type['address'] = $this->buildType($addressTypeConfig);

        $awaited = [
            'min_key'   => 'numeric|min:3',
            'max_key'   => 'required|numeric|max:5',
            'activated' => 'boolean',
            'mobile'    => 'required|string|min:3|max:32',
            'age'       => 'numeric|min:1|max:160',
            'address.street'    => 'string|min:1|max:72',
            'address.zip'       => 'required|string|min:2|max:16',
            'address.location'  => 'required|string|min:1|max:85',
            'address.house_number' => 'string|min:1|max:15',
            'address.country_code' => 'string|min:2|max:2'
        ];

        $this->assertEquals($awaited, $this->newConverter()->toRule($type, 2));

    }

    public function test_convert_nested_type_with_deeper_depth()
    {

        $userTypeConfig = [
            'min_key'   => 'int|min:3',
            'max_key'   => 'float|max:5|not_null',
            'activated' => 'bool',
            'mobile'    => 'phone_number|not_null',
            'age'       => 'int|min:1|max:160'
        ];

        $addressTypeConfig = [
            'street'    => 'street',
            'zip'       => 'postcode|not_null',
            'location'  => 'location|not_null',
            'house_number' => 'house_number',
            'country_code' => 'country_code2'
        ];

        $countryTypeConfig = [
            'country_code' => 'country_code2',
            'name'        => 'string|min:5|max:64'
        ];

        $type = $this->buildType($userTypeConfig);
        $type['address'] = $this->buildType($addressTypeConfig);
        $type['address']['country'] = $this->buildType($countryTypeConfig);

        $awaited = [
            'min_key'   => 'numeric|min:3',
            'max_key'   => 'required|numeric|max:5',
            'activated' => 'boolean',
            'mobile'    => 'required|string|min:3|max:32',
            'age'       => 'numeric|min:1|max:160',
            'address.street'    => 'string|min:1|max:72',
            'address.zip'       => 'required|string|min:2|max:16',
            'address.location'  => 'required|string|min:1|max:85',
            'address.house_number' => 'string|min:1|max:15',
            'address.country_code' => 'string|min:2|max:2',
            'address.country.country_code' => 'string|min:2|max:2',
            'address.country.name' => 'string|min:5|max:64'
        ];

        $this->assertEquals($awaited, $this->newConverter()->toRule($type, 3));

    }

    public function test_convert_nested_type_with_array_relation_filter()
    {

        $userTypeConfig = [
            'min_key'   => 'int|min:3',
            'max_key'   => 'float|max:5|not_null',
            'activated' => 'bool',
            'mobile'    => 'phone_number|not_null',
            'age'       => 'int|min:1|max:160'
        ];

        $addressTypeConfig = [
            'street'    => 'street',
            'zip'       => 'postcode|not_null',
            'location'  => 'location|not_null',
            'house_number' => 'house_number',
            'country_code' => 'country_code2'
        ];

        $businessTypeConfig = [
            'tax_id'    => 'string|min:8',
            'entry'     => 'string|max:90',
        ];

        $type = $this->buildType($userTypeConfig);
        $type['address'] = $this->buildType($addressTypeConfig);
        $type['business'] = $this->buildType($businessTypeConfig);

        $awaited = [
            'min_key'   => 'numeric|min:3',
            'max_key'   => 'required|numeric|max:5',
            'activated' => 'boolean',
            'mobile'    => 'required|string|min:3|max:32',
            'age'       => 'numeric|min:1|max:160',
//             'address.street'    => 'string|min:1|max:72',
//             'address.zip'       => 'required|string|min:2|max:16',
//             'address.location'  => 'required|string|min:1|max:85',
//             'address.house_number' => 'string|min:1|max:15',
//             'address.country_code' => 'string|min:2|max:2',
            'business.tax_id'    => 'string|min:8',
            'business.entry'     => 'string|max:90',
        ];

        $this->assertEquals($awaited, $this->newConverter()->toRule($type, ['business']));

    }

    public function test_convert_nested_type_with_deeper_depth_relation_filter()
    {

        $userTypeConfig = [
            'min_key'   => 'int|min:3',
            'max_key'   => 'float|max:5|not_null',
            'activated' => 'bool',
            'mobile'    => 'phone_number|not_null',
            'age'       => 'int|min:1|max:160'
        ];

        $addressTypeConfig = [
            'street'    => 'street',
            'zip'       => 'postcode|not_null',
            'location'  => 'location|not_null',
            'house_number' => 'house_number',
            'country_code' => 'country_code2'
        ];

        $countryTypeConfig = [
            'country_code' => 'country_code2',
            'name'        => 'string|min:5|max:64'
        ];

        $businessTypeConfig = [
            'tax_id'    => 'string|min:8',
            'entry'     => 'string|max:90',
        ];

        $type = $this->buildType($userTypeConfig);
        $type['address'] = $this->buildType($addressTypeConfig);
        $type['address']['country'] = $this->buildType($countryTypeConfig);
        $type['business'] = $this->buildType($businessTypeConfig);

        $awaited = [
            'min_key'   => 'numeric|min:3',
            'max_key'   => 'required|numeric|max:5',
            'activated' => 'boolean',
            'mobile'    => 'required|string|min:3|max:32',
            'age'       => 'numeric|min:1|max:160',
            'address.street'    => 'string|min:1|max:72',
            'address.zip'       => 'required|string|min:2|max:16',
            'address.location'  => 'required|string|min:1|max:85',
            'address.house_number' => 'string|min:1|max:15',
            'address.country_code' => 'string|min:2|max:2',
            'address.country.country_code' => 'string|min:2|max:2',
            'address.country.name' => 'string|min:5|max:64'
        ];

        $this->assertEquals($awaited, $this->newConverter()->toRule($type, ['address', 'address.country']));

    }

    public function test_convert_nested_type_with_deeper_depth_relation_filter_missing_parents()
    {

        $userTypeConfig = [
            'min_key'   => 'int|min:3',
            'max_key'   => 'float|max:5|not_null',
            'activated' => 'bool',
            'mobile'    => 'phone_number|not_null',
            'age'       => 'int|min:1|max:160'
        ];

        $addressTypeConfig = [
            'street'    => 'street',
            'zip'       => 'postcode|not_null',
            'location'  => 'location|not_null',
            'house_number' => 'house_number',
            'country_code' => 'country_code2'
        ];

        $countryTypeConfig = [
            'country_code' => 'country_code2',
            'name'        => 'string|min:5|max:64'
        ];

        $businessTypeConfig = [
            'tax_id'    => 'string|min:8',
            'entry'     => 'string|max:90',
        ];

        $type = $this->buildType($userTypeConfig);
        $type['address'] = $this->buildType($addressTypeConfig);
        $type['address']['country'] = $this->buildType($countryTypeConfig);
        $type['business'] = $this->buildType($businessTypeConfig);

        $awaited = [
            'min_key'   => 'numeric|min:3',
            'max_key'   => 'required|numeric|max:5',
            'activated' => 'boolean',
            'mobile'    => 'required|string|min:3|max:32',
            'age'       => 'numeric|min:1|max:160',
            'address.street'    => 'string|min:1|max:72',
            'address.zip'       => 'required|string|min:2|max:16',
            'address.location'  => 'required|string|min:1|max:85',
            'address.house_number' => 'string|min:1|max:15',
            'address.country_code' => 'string|min:2|max:2',
            'address.country.country_code' => 'string|min:2|max:2',
            'address.country.name' => 'string|min:5|max:64'
        ];

        $this->assertEquals($awaited, $this->newConverter()->toRule($type, ['address.country']));

    }

    public function test_convert_nested_type_with_with_empty_relations_array_leads_to_depth_1()
    {

        $userTypeConfig = [
            'min_key'   => 'int|min:3',
            'max_key'   => 'float|max:5|not_null',
            'activated' => 'bool',
            'mobile'    => 'phone_number|not_null',
            'age'       => 'int|min:1|max:160'
        ];

        $addressTypeConfig = [
            'street'    => 'street',
            'zip'       => 'postcode|not_null',
            'location'  => 'location|not_null',
            'house_number' => 'house_number',
            'country_code' => 'country_code2'
        ];

        $countryTypeConfig = [
            'country_code' => 'country_code2',
            'name'        => 'string|min:5|max:64'
        ];

        $businessTypeConfig = [
            'tax_id'    => 'string|min:8',
            'entry'     => 'string|max:90',
        ];

        $type = $this->buildType($userTypeConfig);
        $type['address'] = $this->buildType($addressTypeConfig);
        $type['address']['country'] = $this->buildType($countryTypeConfig);
        $type['business'] = $this->buildType($businessTypeConfig);

        $awaited = [
            'min_key'   => 'numeric|min:3',
            'max_key'   => 'required|numeric|max:5',
            'activated' => 'boolean',
            'mobile'    => 'required|string|min:3|max:32',
            'age'       => 'numeric|min:1|max:160',
            'address'   => 'array',
            'business'  => 'array'
        ];

        $this->assertEquals($awaited, $this->newConverter()->toRule($type, []));

    }

    public function test_convert_nested_type_with_less_depth_than_passed()
    {

        $userTypeConfig = [
            'min_key'   => 'int|min:3',
            'max_key'   => 'float|max:5|not_null',
            'activated' => 'bool',
            'mobile'    => 'phone_number|not_null',
            'age'       => 'int|min:1|max:160'
        ];

        $addressTypeConfig = [
            'street'    => 'street',
            'zip'       => 'postcode|not_null',
            'location'  => 'location|not_null',
            'house_number' => 'house_number',
            'country_code' => 'country_code2'
        ];

        $type = $this->buildType($userTypeConfig);
        $type['address'] = $this->buildType($addressTypeConfig);

        $awaited = [
            'min_key'   => 'numeric|min:3',
            'max_key'   => 'required|numeric|max:5',
            'activated' => 'boolean',
            'mobile'    => 'required|string|min:3|max:32',
            'age'       => 'numeric|min:1|max:160',
            'address'   => 'array'
        ];

        $this->assertEquals($awaited, $this->newConverter()->toRule($type, 1));

    }

    public function test_convert_nested_type_with_sequence()
    {

        $userTypeConfig = [
            'min_key'   => 'int|min:3',
            'max_key'   => 'float|max:5|not_null',
            'activated' => 'bool',
            'mobile'    => 'phone_number|not_null',
            'age'       => 'int|min:1|max:160',
            'logins'    => 'sequence|min:1|max:15|itemType:[number|min:1990|max:2017|not_null]'
        ];

        $type = $this->buildType($userTypeConfig);

        $awaited = [
            'min_key'   => 'numeric|min:3',
            'max_key'   => 'required|numeric|max:5',
            'activated' => 'boolean',
            'mobile'    => 'required|string|min:3|max:32',
            'age'       => 'numeric|min:1|max:160',
            'logins'    => 'array|min:1|max:15'
        ];

        $this->assertEquals($awaited, $this->newConverter()->toRule($type, 2));

    }

    protected function buildType(array $config)
    {
        if (!$this->typeFactory) {
            $this->typeFactory = new TypeFactory;
            (new Aliases)->addTo($this->typeFactory);
        }
        return $this->typeFactory->toType($config);
    }

    protected function newConverter()
    {
        return new XTypeToRuleConverter;
    }
}
