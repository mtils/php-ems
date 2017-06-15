<?php

namespace Ems\Testing;

use Ems\TestCase;


require_once __DIR__ .'/CheatTest.php';


class CheatProxyTest extends TestCase
{
    public function test_get_returns_protected_value()
    {
        $tester = new VisibilityTester();
        $tester->setProtected('a');
        $proxy = $this->newProxy($tester);
        $this->assertEquals('a', $proxy->protected);
    }

    public function test_set_sets_protected_value()
    {
        $tester = new VisibilityTester();
        $proxy = $this->newProxy($tester);
        $proxy->protected = 'b';
        $this->assertEquals('b', $tester->getProtected());
    }

    public function test_call_calls_protected_method()
    {
        $tester = new VisibilityTester();
        $proxy = $this->newProxy($tester);

        $this->assertEquals('protected', $proxy->protectedMethod('boo'));
        $this->assertEquals(['boo'], $tester->protectedMethodArgs);

    }

    /**
     * @expectedException InvalidArgumentException
     **/
    public function test_instatiate_with_non_object_throws_exception()
    {
        $proxy = $this->newProxy('no-object');
    }

    protected function newProxy($source)
    {
        return new CheatProxy($source);
    }

}
