<?php

namespace Ems\XType;

use Ems\Contracts\XType\XType;
use Ems\Contracts\Core\Extractor as ExtractorContract;
use Ems\Contracts\XType\TypeProvider as TypeProviderContract;
use Ems\Core\Extractor;

require_once __DIR__ . '/TypeFactoryTest.php';


class TypeProviderTest extends \Ems\TestCase
{
    public function test_implements_interface()
    {
        $this->assertInstanceOf(TypeProviderContract::class, $this->newProvider());
    }

    public function test_xtype_returns_base_type_if_no_class_passed()
    {

        $provider = $this->newProvider();
        $type = $provider->xType(3);
        $this->assertInstanceOf(NumberType::class, $type);
        $this->assertEquals('int', $type->nativeType);

        $type = $provider->xType(3.5);
        $this->assertInstanceOf(NumberType::class, $type);
        $this->assertEquals('float', $type->nativeType);

    }

    public function test_xtype_returns_type_from_nested_path_in_stdClass_object()
    {

        $extractor = $this->newExtractor();

        $extractor->extend('property', function ($object, $key) {

            if (!property_exists($object, $key)) {
                return;
            }

            if (!is_object($object->$key)) {
                return gettype($object->$key);
            }

            return get_class($object->$key);

        });

        $provider = $this->newProvider($extractor);

        $user = (object)[
            'login'     => 'mr-knister',
            'address'   => (object)[
                'country'   => 'USA',
                'city'      => (object)[
                    'name'=>'New York',
                    'abbr'=>'NY',
                    'id'=>88
                ],
                'has_states' => true
            ],
            'permissions' => ['a'=>'b'],
            'weight' => 78.4
        ];

        $loginType = $provider->xType($user, 'login');
        $addressType = $provider->xType($user, 'address');
        $cityIdType = $provider->xType($user, 'address.city.id');
        $hasStatesType = $provider->xType($user, 'address.has_states');
        $permissionsType = $provider->xType($user, 'permissions');
        $weightType = $provider->xType($user, 'weight');

        $this->assertInstanceOf(StringType::class, $loginType);
        $this->assertInstanceOf(ObjectType::class, $addressType);
        $this->assertInstanceOf(NumberType::class, $cityIdType);
        $this->assertInstanceOf(BoolType::class, $hasStatesType);
        $this->assertInstanceOf(ArrayAccessType::class, $permissionsType);
        $this->assertInstanceOf(NumberType::class, $weightType);

        $nullType = $provider->xType($user, 'foo');

    }

    public function test_xtype_returns_class_type_from_extension()
    {

        $provider = $this->newProvider();

        $provider->extend(TypeProviderTest_Sample::class, function($class) {

            $type = new ObjectType(['class'=>$class]);

            $instance = is_object($class) ? $class : new $class;

            foreach ($instance->properties() as $key) {
                $type[$key] = new BoolType;
            }

            return $type;

        });

        $type = $provider->xType(TypeProviderTest_Sample::class);
        $this->assertInstanceOf(ObjectType::class, $type);
        $this->assertInstanceOf(BoolType::class, $type['a']);
        $this->assertInstanceOf(BoolType::class, $type['b']);

        // Trigger cached mode
        $type = $provider->xType(TypeProviderTest_Sample::class);

    }

    public function test_xtype_returns_class_type_from_extension_with_path()
    {

        $provider = $this->newProvider();

        $provider->extend(TypeProviderTest_Sample::class, function($class) {

            $type = new ObjectType(['class'=>$class]);

            $instance = is_object($class) ? $class : new $class;

            foreach ($instance->properties() as $key) {
                $type[$key] = new BoolType;
            }

            return $type;

        });

        $type = $provider->xType(TypeProviderTest_Sample::class, 'a');
        $this->assertInstanceOf(BoolType::class, $type);

        // Access non existant
        $type = $provider->xType(TypeProviderTest_Sample::class, 'c');

    }

    public function test_xtype_returns_SelfExplanatory_type()
    {

        $user = new TypeFactoryTest_User;

        $provider = $this->newProvider();

        $userType = $provider->xType($user);

        $this->assertInstanceOf(ObjectType::class, $userType);

        $this->assertInstanceOf(NumberType::class, $userType['id']);

        $loginType = $provider->xType($user, 'login');

        $this->assertInstanceOf(StringType::class, $loginType);
        $this->assertEquals(5, $loginType->min);
        $this->assertEquals(255, $loginType->max);

        // Check caching
        $this->assertSame($provider->xType($user, 'login'), $provider->xType($user)['login']);

    }

    public function test_xtype_returns_SelfExplanatory_type_with_nested_path()
    {

        $user = new TypeFactoryTest_User;

        $provider = $this->newProvider();

        $countryNameType = $provider->xType($user, 'address.country.iso_code');

        $this->assertInstanceOf(StringType::class, $countryNameType);
        $this->assertEquals(2, $countryNameType->min);
        $this->assertEquals(2, $countryNameType->max);

        // Check caching
        $this->assertSame($provider->xType($user, 'address.country')['iso_code'], $provider->xType($user, 'address.country.iso_code'));

    }

    public function test_xtype_returns_SelfExplanatory_type_with_nested_path_on_sequence_relation()
    {

        $user = new TypeFactoryTest_User;

        $provider = $this->newProvider();

        $tagNameType = $provider->xType($user, 'tags.name');

        $this->assertInstanceOf(StringType::class, $tagNameType);
        $this->assertEquals(5, $tagNameType->min);
        $this->assertEquals(128, $tagNameType->max);

        // Check caching
        $this->assertSame($provider->xType($user, 'tags')->itemType['name'], $provider->xType($user, 'tags.name'));

    }

    public function test_xtype_returns_null_if_SelfExplanatory_path_not_found()
    {

        $user = new TypeFactoryTest_User;

        $provider = $this->newProvider();

        $this->assertNull($provider->xType($user, 'foo'));
        $this->assertNull($provider->xType($user, 'tags.foo'));

    }

    protected function newProvider(ExtractorContract $extractor=null, TemplateTypeFactory $templateFactory=null, TypeFactory $typeFactory=null)
    {
        $extractor = $extractor ?: $this->newExtractor();
        $templateFactory = $templateFactory ?: $this->newTemplateFactory();
        $typeFactory = $typeFactory ?: $this->newTypeFactory();
        return new TypeProvider($extractor, $templateFactory, $typeFactory);
    }

    protected function newExtractor()
    {
        return new Extractor;
    }

    protected function newTemplateFactory()
    {
        return new TemplateTypeFactory;
    }

    protected function newTypeFactory()
    {
        return new TypeFactory;
    }

    protected function mockExtractor()
    {
        return $this->mock(ExtractorContract::class);
    }

    protected function mockTemplateFactory()
    {
        return $this->mock(TemplateTypeFactory::class);
    }

}


class TypeProviderTest_Sample
{
    public function properties()
    {
        return ['a', 'b'];
    }
}
