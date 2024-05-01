<?php

namespace Ems\Core\Patterns;

use Ems\Contracts\Core\Extendable;
use Ems\Testing\LoggingCallable;
use OutOfBoundsException;

class ExtendableByClassHierarchyTraitTest extends \Ems\TestCase
{
    public function test_implements_interface()
    {
        $this->assertInstanceOf(
            Extendable::class,
            $this->newExtendable()
        );
    }

    public function test_getExtension_returns_direct_match()
    {

        $object = $this->newExtendable();

        $ext = new Extension('a');

        $object->extend(Base::class, $ext);

        $this->assertSame($ext, $object->getExtension(Base::class));

    }

    public function test_getExtension_returns_match_of_parent_class()
    {

        $object = $this->newExtendable();

        $ext = new Extension('a');

        $object->extend(Base::class, $ext);

        $this->assertSame($ext, $object->getExtension(ExtendedBase::class));

        $this->assertSame($ext, $object->getExtension(ExtendedBase::class));

    }

    public function test_getExtension_returns_right_extension_per_hierarchy()
    {

        $object = $this->newExtendable();

        $baseExt = new Extension('a');
        $extendedExt = new Extension('b');

        $object->extend(Base::class, $baseExt);
        $object->extend(ExtendedBase::class, $extendedExt);

        $this->assertSame($baseExt, $object->getExtension(Base::class));
        $this->assertSame($extendedExt, $object->getExtension(ExtendedBase::class));
        $this->assertSame($extendedExt, $object->getExtension(EvenMoreExtendedBase::class));
    }

    public function test_getExtension_returns_right_extension_per_hierarchy2()
    {

        $object = $this->newExtendable();

        $baseExt = new Extension('a');
        $extendedExt = new Extension('b');

        $object->extend(Base::class, $baseExt);
        $object->extend(EvenMoreExtendedBase::class, $extendedExt);

        $this->assertSame($baseExt, $object->getExtension(Base::class));
        $this->assertSame($baseExt, $object->getExtension(ExtendedBase::class));
        $this->assertSame($extendedExt, $object->getExtension(EvenMoreExtendedBase::class));


    }

    public function test_getExtensions_returns_assigned_classes()
    {

        $object = $this->newExtendable();

        $baseExt = new Extension('a');
        $extendedExt = new Extension('b');

        $object->extend(Base::class, $baseExt);
        $object->extend(EvenMoreExtendedBase::class, $extendedExt);

        $this->assertEquals([Base::class, EvenMoreExtendedBase::class], $object->extensions());


    }

    public function test_getExtension_throws_OutOfBoundsException_if_extension_not_found()
    {
        $this->expectException(OutOfBoundsException::class);

        $object = $this->newExtendable();

        $baseExt = new Extension('a');
        $extendedExt = new Extension('b');

        $object->extend(Base::class, $baseExt);
        $object->extend(EvenMoreExtendedBase::class, $extendedExt);

        $object->getExtension('stdClass');

    }

    public function test_getExtension_throws_OutOfBoundsException_if_no_extensions_assigned()
    {
        $this->expectException(OutOfBoundsException::class);

        $object = $this->newExtendable();

        $object->getExtension('stdClass');

    }

    public function test_getExtension_throws_OutOfBoundsException_if_no_matching_extension()
    {
        $this->expectException(OutOfBoundsException::class);

        $object = $this->newExtendable();

        $baseExt = new Extension('a');

        $object->extend(stdClass::class, $baseExt);
        $object->extend(static::class, $baseExt);

        $object->getExtension(EvenMoreExtendedBase::class);

    }

    protected function newExtendable()
    {
        return new ExtendableByClassHierarchyTraitTestObject();
    }

}

class ExtendableByClassHierarchyTraitTestObject implements Extendable
{
    use ExtendableByClassHierarchyTrait;
}

class Base{}

class ExtendedBase extends Base {}

class EvenMoreExtendedBase extends ExtendedBase {}

class Extension
{
    public $name = 'a';

    public function __construct($name='a')
    {
        $this->name = $name;
    }

    public function __invoke()
    {
    }
}
