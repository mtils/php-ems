<?php


namespace Ems\Core\Support;


use Ems\Contracts\Core\SupportsCustomFactory;
use Ems\Core\Exceptions\UnConfiguredException;
use Ems\Testing\LoggingCallable;



class CustomFactorySupportTest extends \Ems\TestCase
{

    public function test_implements_interface()
    {
        $this->assertInstanceOf(SupportsCustomFactory::class, $this->newObject());
    }

    public function test_it_creates_without_callable()
    {
        $object = $this->newObject()->make(CreateMe::class);
        $this->assertInstanceOf(CreateMe::class, $object);
    }

    public function test_it_creates_without_callable_and_parameters()
    {
        $object = $this->newObject()->make(CreateMeWithParameters::class, [[],new \stdClass]);
        $this->assertInstanceOf(CreateMeWithParameters::class, $object);
    }

    public function test_it_creates_with_property_fixed_abstract()
    {
        $factory = new CustomFactorySupportWithFixedAbstract;
        $object = $factory->make();
        $this->assertInstanceOf(CreateMe::class, $object);
    }

    public function test_it_createObject_without_class_and_property_throws_exception()
    {
        $this->expectException(
            UnConfiguredException::class
        );
        $object = $this->newObject()->make();
    }

    public function test_it_creates_with_callable()
    {

        $creator = new LoggingCallable(function ($class, array $parameters=[]) {
            return new CreateMe;
        });

        $factory = $this->newObject()->createObjectsBy($creator);

        $object = $factory->make(CreateMe::class);

        $this->assertInstanceOf(CreateMe::class, $object);
        $this->assertCount(1, $creator);
        $this->assertEquals(CreateMe::class, $creator->arg(0));
    }

    public function test_it_creates_with_callable_and_parameters()
    {

        $creator = new LoggingCallable(function ($class, array $parameters=[]) {
            return new CreateMe;
        });

        $factory = $this->newObject()->createObjectsBy($creator);

        $object = $factory->make(CreateMe::class, ['a', 'b']);

        $this->assertInstanceOf(CreateMe::class, $object);
        $this->assertCount(1, $creator);
        $this->assertEquals(CreateMe::class, $creator->arg(0));
        $this->assertEquals(['a', 'b'], $creator->arg(1));
    }

    protected function newObject()
    {
        return new CustomFactorySupportObject();
    }
}

class CreateMe {}



class CreateMeWithParameters
{
    public function __construct(array $some, \stdClass $other)
    {

    }
}

class CustomFactorySupportObject implements SupportsCustomFactory
{
    use CustomFactorySupport;

    public function make($class=null, array $parameters=[])
    {
        return $this->createObject($class, $parameters);
    }
}

class CustomFactorySupportWithFixedAbstract extends CustomFactorySupportObject {
    protected $factoryAbstract = CreateMe::class;
}
