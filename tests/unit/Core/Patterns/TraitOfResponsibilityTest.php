<?php

namespace Ems\Core\Patterns;

use Countable;

class TraitOfResponsibilityTest extends \Ems\TestCase
{
    public function test_implements_interface()
    {
        $this->assertInstanceOf(
            TestChainInterface::class,
            $this->newChain()
        );
    }

    public function test_add_second_chain()
    {
        $chain = $this->newChain();
        $second = new ImplementsTestChain;
        $this->assertSame($chain, $chain->add($second));
    }

    public function test_add_chain_twice_adds_it_only_once()
    {
        $chain = $this->newChain();
        $second = new ImplementsTestChain;
        $this->assertSame($chain, $chain->add($second));
        $this->assertCount(1, $chain);
        $this->assertSame($chain, $chain->add($second));
        $this->assertCount(1, $chain);
    }

    public function test_addIfNoneOfClass_adds_only_if_class_unknown()
    {
        $chain = $this->newChain();
        $second = new ImplementsTestChain;
        $third = new ImplementsTestChain;
        $this->assertCount(0, $chain);
        $this->assertSame($chain, $chain->addIfNoneOfClass($second));
        $this->assertCount(1, $chain);
        $this->assertSame($chain, $chain->addIfNoneOfClass($third));
        $this->assertCount(1, $chain);
    }

    public function test_remove_removes_added_object()
    {
        $chain = $this->newChain();
        $second = new ImplementsTestChain;
        $this->assertSame($chain, $chain->add($second));
        $this->assertCount(1, $chain);
        $this->assertSame($chain, $chain->remove($second));
        $this->assertCount(0, $chain);
    }

    public function test_findReturningTrue_finds_right_handler()
    {
        $falseHandler = new ImplementsTestChain;
        $trueHandler = new ImplementsTestChain;

        $falseHandler->shouldCan = false;
        $trueHandler->shouldCan = true;

        $chain = $this->newChain();
        $chain->add($falseHandler);
        $chain->add($trueHandler);

        $this->assertSame($trueHandler, $chain->can('foo', 'bar'));
    }

    public function test_findReturningTrue_finds_in_right_order()
    {
        $trueHandler = new ImplementsTestChain;

        $handlers = [
            new ImplementsTestChain,
            new ImplementsTestChain,
            new ImplementsTestChain
        ];

        $chain = $this->newChain();

        foreach ($handlers as $handler) {
            $chain->add($handler);
        }

        $this->assertSame($handlers[2], $chain->can('foo', 'bar'));
    }

    public function test_findReturningTrue_finds_in_fifo_order()
    {
        $trueHandler = new ImplementsTestChain;

        $handlers = [
            new ImplementsTestChain,
            new ImplementsTestChain,
            new ImplementsTestChain
        ];

        $chain = $this->newChain();
        $chain->callReversed = false;

        foreach ($handlers as $handler) {
            $chain->add($handler);
        }

        $this->assertSame($handlers[0], $chain->can('foo', 'bar'));
    }

    public function test_findReturningTrueOrFail_finds_right_handler()
    {
        $falseHandler = new ImplementsTestChain;
        $trueHandler = new ImplementsTestChain;

        $falseHandler->shouldCan = false;
        $trueHandler->shouldCan = true;

        $chain = $this->newChain();
        $chain->add($falseHandler);
        $chain->add($trueHandler);

        $this->assertSame($trueHandler, $chain->canOrFail('foo', 'bar'));
    }

    /**
     * @expectedException \InvalidArgumentException
     **/
    public function test_add_wrong_type_throws_exception()
    {
        $this->newChain()->add([]);
    }

    /**
     * @expectedException \InvalidArgumentException
     **/
    public function test_add_wrong_class_throws_exception()
    {
        $this->newChain()->add(new DoesNotImplementTestChain);
    }

    /**
     * @expectedException \InvalidArgumentException
     **/
    public function test_add_wrong_manually_fixed_class_throws_exception()
    {
        (new FixedClassChain)->add($this->newChain());
    }

    /**
     * @expectedException \InvalidArgumentException
     **/
    public function test_add_to_non_implementing_class()
    {
        (new TestChainWithoutInterfaces)->add(new ImplementsTestChain);
    }

    public function test_findReturningTrue_returns_null_if_no_handler_found()
    {
        $falseHandler = new ImplementsTestChain;
        $trueHandler = new ImplementsTestChain;

        $falseHandler->shouldCan = false;
        $trueHandler->shouldCan = false;

        $chain = $this->newChain();
        $chain->add($falseHandler);
        $chain->add($trueHandler);

        $this->assertNull($chain->can('foo', 'bar'));
    }

    /**
     * @expectedException Ems\Core\Exceptions\HandlerNotFoundException
     **/
    public function test_findReturningTrueOrFail_throws_exception_if_no_handler_found()
    {
        $falseHandler = new ImplementsTestChain;
        $trueHandler = new ImplementsTestChain;

        $falseHandler->shouldCan = false;
        $trueHandler->shouldCan = false;

        $chain = $this->newChain();
        $chain->add($falseHandler);
        $chain->add($trueHandler);

        $this->assertNull($chain->canOrFail('foo', 'bar'));
    }

    public function test_firstNotNullResult_returns_result_of_found_handler()
    {
        $falseHandler = new ImplementsTestChain;
        $trueHandler = new ImplementsTestChain;

        $falseHandler->shouldCan = false;
        $trueHandler->shouldCan = false;

        $handlers = [
            new WithoutCheckMethod(false),
            new WithoutCheckMethod(false),
            new WithoutCheckMethod(true),
            new WithoutCheckMethod(false)
        ];

        $chain = new ChainWithoutCheckMethod;

        foreach ($handlers as $handler) {
            $chain->add($handler);
        }

        $this->assertEquals('foo', $chain->run('foo'));
    }

    public function test_firstNotNullResult_returns_null_if_no_handler_found()
    {
        $falseHandler = new ImplementsTestChain;
        $trueHandler = new ImplementsTestChain;

        $falseHandler->shouldCan = false;
        $trueHandler->shouldCan = false;

        $handlers = [
            new WithoutCheckMethod(false),
            new WithoutCheckMethod(false),
            new WithoutCheckMethod(false),
            new WithoutCheckMethod(false)
        ];

        $chain = new ChainWithoutCheckMethod;

        foreach ($handlers as $handler) {
            $chain->add($handler);
        }

        $this->assertNull($chain->run('foo'));
    }

    public function test_firstNotNullResultOrFail_returns_result_of_found_handler()
    {
        $falseHandler = new ImplementsTestChain;
        $trueHandler = new ImplementsTestChain;

        $falseHandler->shouldCan = false;
        $trueHandler->shouldCan = false;

        $handlers = [
            new WithoutCheckMethod(false),
            new WithoutCheckMethod(false),
            new WithoutCheckMethod(true),
            new WithoutCheckMethod(false)
        ];

        $chain = new ChainWithoutCheckMethod;

        foreach ($handlers as $handler) {
            $chain->add($handler);
        }

        $this->assertEquals('foo', $chain->runOrFail('foo'));
    }

    /**
     * @expectedException Ems\Core\Exceptions\HandlerNotFoundException
     **/
    public function test_firstNotNullResultOrFail_throws_exception_if_no_handler_found()
    {
        $falseHandler = new ImplementsTestChain;
        $trueHandler = new ImplementsTestChain;

        $falseHandler->shouldCan = false;
        $trueHandler->shouldCan = false;

        $handlers = [
            new WithoutCheckMethod(false),
            new WithoutCheckMethod(false),
            new WithoutCheckMethod(false),
            new WithoutCheckMethod(false)
        ];

        $chain = new ChainWithoutCheckMethod;

        foreach ($handlers as $handler) {
            $chain->add($handler);
        }

        $this->assertNull($chain->runOrFail('foo'));
    }

    public function test_contains_returns_true_if_handler_found()
    {
        $falseHandler = new ImplementsTestChain;
        $trueHandler = new ImplementsTestChain;

        $falseHandler->shouldCan = false;
        $trueHandler->shouldCan = false;

        $chain = $this->newChain();
        $chain->add($falseHandler);
        $chain->add($trueHandler);

        $this->assertTrue($chain->contains($falseHandler));
    }

    public function test_contains_returns_false_if_handler_not_found()
    {
        $falseHandler = new ImplementsTestChain;
        $trueHandler = new ImplementsTestChain;

        $falseHandler->shouldCan = false;
        $trueHandler->shouldCan = false;

        $chain = $this->newChain();
        $chain->add($trueHandler);

        $this->assertFalse($chain->contains($falseHandler));
    }

    protected function newChain()
    {
        return new TestChain;
    }

}

interface TestChainInterface
{
}

class TestChainWithoutInterfaces
{
    use TraitOfResponsibility;

    public function can($foo, $bar)
    {
        return $this->findReturningTrue('can', $foo, $bar);
    }

    public function canOrFail($foo, $bar)
    {
        return $this->findReturningTrueOrFail('can', $foo, $bar);
    }

    public function run($foo, $bar)
    {
        return func_get_args();
    }

    public function returnTrue()
    {
        return true;
    }

    public function returnFalse()
    {
        return false;
    }
}

class TestChain extends TestChainWithoutInterfaces implements TestChainInterface, Countable
{
    public $callReversed = true;
}

class FixedClassChain extends TestChain
{
    protected $allow = self::class;
}

class ImplementsTestChain implements TestChainInterface
{

    public $shouldCan = true;

    public function can($foo, $bar)
    {
        return $this->shouldCan;
    }

    public function run($foo, $bar)
    {
        return func_get_args();
    }
}

class DoesNotImplementTestChain
{
}

class WithoutCheckMethod implements TestChainInterface
{
    public $returnValue = false;

    public function __construct($returnValue)
    {
        $this->returnValue = $returnValue;
    }

    public function run($foo)
    {
        return $this->returnValue ? $foo : null;
    }
}

class ChainWithoutCheckMethod implements TestChainInterface
{
    use TraitOfResponsibility;

    public function run($foo)
    {
        return $this->firstNotNullResult('run', $foo);
    }

    public function runOrFail($foo)
    {
        return $this->firstNotNullResultOrFail('run', $foo);
    }
}
