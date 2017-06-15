<?php

namespace Ems\Testing;

use Ems\TestCase;

class CheatTest extends TestCase
{
    public function test_get_returns_public_value()
    {
        $tester = new VisibilityTester();
        $tester->public = 'a';
        $this->assertEquals('a', Cheat::get($tester, 'public'));
    }

    public function test_get_returns_protected_value()
    {
        $tester = new VisibilityTester();
        $tester->setProtected('a');
        $this->assertEquals('a', Cheat::get($tester, 'protected'));
    }

    public function test_get_returns_private_value()
    {
        $tester = new VisibilityTester();
        $tester->setPrivate('a');
        $this->assertEquals('a', Cheat::get($tester, 'private'));
    }

    public function test_set_changes_private_value()
    {
        $tester = new VisibilityTester();

        Cheat::set($tester, 'private', 'foobar');

        $this->assertEquals('foobar', $tester->getPrivate());
    }

    public function test_call_calls_public_method()
    {
        $tester = new VisibilityTester();
        $args = ['a','b','c'];
        $this->assertEquals('public', Cheat::call($tester, 'publicMethod', $args));
        $this->assertEquals($args, $tester->publicMethodArgs);
    }

    public function test_call_calls_protected_method()
    {
        $tester = new VisibilityTester();
        $args = ['a','b','c'];
        $this->assertEquals('protected', Cheat::call($tester, 'protectedMethod', $args));
        $this->assertEquals($args, $tester->protectedMethodArgs);
    }

    public function test_call_calls_private_method()
    {
        $tester = new VisibilityTester();
        $args = ['a','b','c'];
        $this->assertEquals('private', Cheat::call($tester, 'privateMethod', $args));
        $this->assertEquals($args, $tester->privateMethodArgs);
    }

    public function test_a_returns_CheatProxy()
    {
        $tester = new VisibilityTester();
        $proxy = Cheat::a($tester);
        $this->assertInstanceOf(CheatProxy::class, $proxy);
    }

    public function test_an_returns_CheatProxy()
    {
        $tester = new VisibilityTester();
        $proxy = Cheat::an($tester);
        $this->assertInstanceOf(CheatProxy::class, $proxy);
    }

}

class VisibilityTester
{
    public $public;

    protected $protected;

    private $private;

    public $publicMethodArgs;

    public $protectedMethodArgs;

    public $privateMethodArgs;

    public function getProtected()
    {
        return $this->protected;
    }

    public function setProtected($value)
    {
        $this->protected = $value;
        return $this;
    }

    public function getPrivate()
    {
        return $this->private;
    }

    public function setPrivate($value)
    {
        $this->private = $value;
        return $this;
    }

    public function publicMethod()
    {
        $this->publicMethodArgs = func_get_args();
        return 'public';
    }

    protected function protectedMethod()
    {
        $this->protectedMethodArgs = func_get_args();
        return 'protected';
    }

    private function privateMethod()
    {
        $this->privateMethodArgs = func_get_args();
        return 'private';
    }
}
