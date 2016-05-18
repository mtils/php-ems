<?php

namespace Ems\Testing;

use Ems\TestCase;

class LoggingCallableTest extends TestCase
{

    public function test_invokes_are_getting_logged()
    {
        $callable = new LoggingCallable;

        call_user_func($callable, 'a');

        $this->assertEquals(1, count($callable));

        call_user_func($callable, 'b');

        $this->assertEquals(2, count($callable));
    }

    public function test_args_returns_last_args()
    {
        $callable = new LoggingCallable;

        call_user_func($callable, 'a');

        $this->assertEquals(['a'], $callable->args());

        call_user_func($callable, 'b', 'c');

        $this->assertEquals(['b','c'], $callable->args());

    }

    public function test_args_returns_correct_args_by_index()
    {
        $callable = new LoggingCallable;

        call_user_func($callable, 'a');

        $this->assertEquals(['a'], $callable->args(0));

        call_user_func($callable, 'b', 'c');

        $this->assertEquals(['a'], $callable->args(0));

        $this->assertEquals(['b','c'], $callable->args(1));

    }

    public function test_arg_returns_last_arg()
    {
        $callable = new LoggingCallable;

        call_user_func($callable, 'a');

        $this->assertEquals('a', $callable->arg(0));

        call_user_func($callable, 'b', 'c');

        $this->assertEquals('b', $callable->arg(0));
        $this->assertEquals('c', $callable->arg(1));

    }

    public function test_arg_returns_correct_arg_by_index()
    {
        $callable = new LoggingCallable;

        call_user_func($callable, 'a');

        $this->assertEquals('a', $callable->arg(0, 0));

        call_user_func($callable, 'b', 'c');

        $this->assertEquals('a', $callable->arg(0, 0));

        $this->assertEquals('b', $callable->arg(0,1));

        $this->assertEquals('c', $callable->arg(1,1));

    }

}