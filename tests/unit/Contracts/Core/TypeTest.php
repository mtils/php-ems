<?php
/**
 *  * Created by mtils on 17.12.17 at 11:13.
 **/

namespace Ems\Contracts\Core;


use ArrayIterator;
use Countable;
use Ems\Core\Collections\OrderedList;
use Ems\TestCase;
use Traversable;

class TypeTest extends TestCase
{

    public function test_is_returns_true_on_null_if_nullable()
    {
        $this->assertTrue(Type::is(null, 'string', true));
        $this->assertFalse(Type::is(null, 'string'));
    }

    public function test_is_returns_true_if_all_types_matches()
    {
        $this->assertTrue(Type::is(15, ['numeric', 'int']));
        $this->assertFalse(Type::is(15, ['numeric', 'float']));
    }

    public function test_is_returns_true_if_traversable()
    {
        $this->assertTrue(Type::is(new ArrayIterator(), Traversable::class));
        $this->assertTrue(Type::is([], Traversable::class));
    }

    public function test_is_returns_true_if_countable()
    {
        $this->assertTrue(Type::is(new ArrayIterator(), Countable::class));
        $this->assertTrue(Type::is([], Countable::class));
    }

    public function test_toBool_returns_right_values()
    {
        $this->assertTrue(Type::toBool('') === false);
        $list = new OrderedList([1,2,3]);
        $this->assertTrue(Type::toBool($list) === true);
        $this->assertTrue(Type::toBool(new OrderedList()) === false);
        $this->assertTrue(Type::toBool(' ') === false);
        $this->assertTrue(Type::toBool('0') === false);
        $this->assertTrue(Type::toBool(false) === false);
        $this->assertTrue(Type::toBool('false') === false);
        $this->assertTrue(Type::toBool('true') === true);
        $this->assertTrue(Type::toBool(new \Ems\Core\Url()) === false);

    }

    public function test_toArray_throws_exception_when_not_castable()
    {
        $this->expectException(
            \Ems\Contracts\Core\Exceptions\TypeException::class
        );
        Type::toArray(0.127);
    }

    public function test_force_throws_exception_if_type_does_not_match()
    {
        $this->expectException(
            \Ems\Contracts\Core\Exceptions\TypeException::class
        );
        Type::force(145, 'string');
    }

    public function test_traits_returns_directly_used_traits()
    {
        // Test unnested traits
        $this->assertEquals([
            TypeTest_Trait1::class => TypeTest_Trait1::class
        ], Type::traits(TypeTest_TraitTest1::class));

        // Test nested traits
        $this->assertEquals([
            TypeTest_Trait1::class => TypeTest_Trait1::class,
            TypeTest_Trait2::class => TypeTest_Trait2::class,
        ], Type::traits(TypeTest_TraitTest12::class));

        $this->assertEquals([], Type::traits(TypeTest_TraitTest1_extended::class));
    }

    public function test_traits_returns_traits_from_parent_classes()
    {
        // Test one trait of parent class
        $this->assertEquals([
            TypeTest_Trait1::class => TypeTest_Trait1::class
        ], Type::traits(TypeTest_TraitTest1_extended::class, true));

        $this->assertEquals([
            TypeTest_Trait1::class => TypeTest_Trait1::class
        ], Type::traits(TypeTest_TraitTest1_extended2::class, true));

    }

    public function test_traits_returns_traits_used_by_traits()
    {
        // Test one trait of parent class
        $this->assertEquals([
            TypeTest_Trait1::class => TypeTest_Trait1::class,
            TypeTest_Trait2::class => TypeTest_Trait2::class,
            TypeTest_SubTrait1::class => TypeTest_SubTrait1::class
        ], Type::traits(TypeTest_TraitTest12::class, true));

        $this->assertEquals([
            TypeTest_Trait3::class => TypeTest_Trait3::class,
            TypeTest_SubTrait2::class => TypeTest_SubTrait2::class,
            TypeTest_SubTrait_of_SubTrait::class => TypeTest_SubTrait_of_SubTrait::class
        ], Type::traits(TypeTest_TraitTest3::class, true));

        $this->assertEquals([
            TypeTest_SubTrait1::class => TypeTest_SubTrait1::class,
            TypeTest_SubTrait2::class => TypeTest_SubTrait2::class,
            TypeTest_SubTrait_of_SubTrait::class => TypeTest_SubTrait_of_SubTrait::class,
            TypeTest_Trait4::class => TypeTest_Trait4::class
        ], Type::traits(TypeTest_TraitTest4::class, true));

    }

    public function test_classInFile_returns_class_with_namespace()
    {
        $this->assertEquals(self::class, Type::classInFile(__FILE__));
    }

    public function test_classInFile_returns_class()
    {
        $file = $this->dirOfTests('database/migrations/2014_05_26_092001_create_users_table.php');
        $this->assertEquals('CreateUsersTable', Type::classInFile($file));
    }

    public function test_classInFile_returns_anonymous_class()
    {
        $file = $this->dirOfTests('data/reflection/anonymous-class-returned.php');
        $this->assertEquals(Type::ANONYMOUS_CLASS, Type::classInFile($file));
    }

    public function test_classInFile_returns_anonymous_class_after_other_instantiation()
    {
        $file = $this->dirOfTests('data/reflection/anonymous-class-after-instantiate-returned.php');
        $this->assertEquals(Type::ANONYMOUS_CLASS, Type::classInFile($file));
    }

    public function test_classInFile_returns_real_class_after_other_instantiation()
    {
        $file = $this->dirOfTests('data/reflection/class-after-anonymous-returned.php');
        $this->assertEquals('AfterAnonymous', Type::classInFile($file));
    }
    //class-after-function-with-class
    public function test_classInFile_returns_real_class_after_class_in_function()
    {
        $file = $this->dirOfTests('data/reflection/class-after-function-with-class.php');
        $this->assertEquals('AfterFunctionWithClass', Type::classInFile($file));
    }
}

trait TypeTest_SubTrait_of_SubTrait
{

}

trait TypeTest_SubTrait2
{
    use TypeTest_SubTrait_of_SubTrait;
}

trait TypeTest_SubTrait1
{

}

trait TypeTest_Trait1
{

}

trait TypeTest_Trait2
{
    use TypeTest_SubTrait1;
}

trait TypeTest_Trait3
{
    use TypeTest_SubTrait2;
}

trait TypeTest_Trait4
{
    use TypeTest_SubTrait1;
    use TypeTest_SubTrait2;
}

class TypeTest_TraitTest1
{
    use TypeTest_Trait1;
}

class TypeTest_TraitTest1_extended extends TypeTest_TraitTest1
{
    //
}

class TypeTest_TraitTest12
{
    use TypeTest_Trait1;
    use TypeTest_Trait2;

}

class TypeTest_TraitTest3
{
    use TypeTest_Trait3;
}

class TypeTest_TraitTest3_extended extends TypeTest_TraitTest3
{
    //
}

class TypeTest_TraitTest4
{
    use TypeTest_Trait4;
}

class TypeTest_TraitTest1_extended2 extends TypeTest_TraitTest1_extended
{
    //
}