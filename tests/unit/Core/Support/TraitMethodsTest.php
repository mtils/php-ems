<?php


namespace Ems\Core\Support;


use Ems\Contracts\Core\SupportsCustomFactory;
use Ems\Testing\Cheat;
use Ems\Testing\LoggingCallable;
use function func_get_args;


class TraitMethodsTest extends \Ems\TestCase
{

    public function test_call_traitless_class_does_nothing()
    {
        $obj1 = new TraitMethodsTest_Class1();

        $this->assertNull(Cheat::call($obj1, 'callOnAllTraits', ['boot']));
    }

    public function test_call_class_with_nonexisting_hook_does_nothing()
    {
        $obj1 = new TraitMethodsTest_Class2();

        $this->assertNull(Cheat::call($obj1, 'callOnAllTraits', ['fire']));
    }

    public function test_call_existing_hook_without_args()
    {
        $obj1 = new TraitMethodsTest_Class2();

        $this->assertNull(Cheat::call($obj1, 'callOnAllTraits', ['boot']));

        $this->assertSame([], $obj1->args);
    }

    public function test_call_existing_hook_with_args()
    {
        $obj1 = new TraitMethodsTest_Class2();
        $args = ['foo', 'bar'];

        $this->assertNull(Cheat::call($obj1, 'callOnAllTraits', ['boot', $args]));

        $this->assertEquals($args, $obj1->args);
    }

    public function test_call_distinct_hooks_with_args()
    {
        $obj1 = new TraitMethodsTest_Class3();
        $args = ['foo', 'bar'];
        $args2 = ['rebecca', 'tom'];

        $this->assertNull(Cheat::call($obj1, 'callOnAllTraits', ['boot', $args]));

        $this->assertEquals($args, $obj1->args);
        $this->assertNull($obj1->args2);

        $this->assertNull(Cheat::call($obj1, 'callOnAllTraits', ['init', $args2]));

        $this->assertEquals($args, $obj1->args);
        $this->assertEquals($args2, $obj1->args2);
    }
}


class TraitMethodsTest_Class1
{
    use TraitMethods;
}

trait TraitMethodTest_Trait1
{
    public $args;

    public function bootTraitMethodTest_Trait1()
    {
        $this->args = func_get_args();
    }
}

trait TraitMethodTest_Trait2
{
    public $args2;

    public function initTraitMethodTest_Trait2()
    {
        $this->args2 = func_get_args();
    }
}

class TraitMethodsTest_Class2
{
    use TraitMethods;
    use TraitMethodTest_Trait1;
}

class TraitMethodsTest_Class3
{
    use TraitMethods;
    use TraitMethodTest_Trait1;
    use TraitMethodTest_Trait2;
}