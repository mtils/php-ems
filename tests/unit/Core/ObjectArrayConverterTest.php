<?php
/**
 *  * Created by mtils on 13.09.20 at 20:37.
 **/

namespace unit\Core;


use Ems\Core\ObjectArrayConverter;
use Ems\Contracts\Core\ObjectArrayConverter as ObjectArrayConverterContract;
use Ems\TestCase;
use stdClass;

use function get_object_vars;

class ObjectArrayConverterTest extends TestCase
{
    /**
     * @test
     */
    public function it_implements_interface()
    {
        $this->assertInstanceOf(ObjectArrayConverterContract::class, $this->make());
    }

    /**
     * @test
     */
    public function create_simple_object()
    {
        $data = [
            'id'    =>  123456,
            'user'  => 'login_name',
            'email' => 'michael@outback.com'
        ];

        $object = $this->make()->fromArray(stdClass::class, $data);
        $this->assertEquals((object)$data, $object);
    }

    /**
     * @test
     */
    public function create_simple_nested_object()
    {
        $data = [
            'id'    =>  123456,
            'user'  => 'login_name',
            'email' => 'michael@outback.com',
            'address'   => [
                'street'    => 'Elmstreet 5',
                'city'      => 'Nightmare City'
            ]
        ];

        $object = $this->make()->fromArray(stdClass::class, $data);
        $this->assertEquals((object)$data['address'], $object->address);
    }

    /**
     * @test
     */
    public function create_simple_custom_object()
    {
        $data = [
            'id'    =>  123456,
            'user'  => 'login_name',
            'email' => 'michael@outback.com',
            'address'   => [
                'street'    => 'Elmstreet 5',
                'city'      => 'Nightmare City'
            ]
        ];
        $object = $this->make()->fromArray(ObjectArrayConverterTest_public::class, $data);
        $this->assertInstanceOf(ObjectArrayConverterTest_public::class, $object);
        $this->assertEquals((object)$data['address'], $object->address);
        $this->assertEquals($data['id'], $object->id);
    }

    /**
     * @test
     */
    public function create_simple_custom_nested_object()
    {
        $data = [
            'id'    =>  123456,
            'user'  => 'login_name',
            'email' => 'michael@outback.com',
            'address'   => [
                'street'    => 'Elmstreet 5',
                'city'      => 'Nightmare City'
            ]
        ];
        $converter = $this->make();
        $converter->setTypeProvider(function () {
            return ObjectArrayConverterTest_public::class;
        });

        $object = $converter->fromArray(ObjectArrayConverterTest_public::class, $data);
        $this->assertInstanceOf(ObjectArrayConverterTest_public::class, $object);
        $this->assertInstanceOf(ObjectArrayConverterTest_public::class, $object->address);
        $this->assertEquals($data['address'], get_object_vars($object->address));
//        $this->assertEquals($data['id'], $object->id);
    }

    /**
     * @return ObjectArrayConverter
     */
    protected function make()
    {
        return new ObjectArrayConverter();
    }
}

class ObjectArrayConverterTest_public
{

}