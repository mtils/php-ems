<?php

namespace Ems;

use Mockery;

class TestCase extends \PHPUnit_Framework_TestCase
{
    public function mock($class)
    {
        $mock = Mockery::mock($class);
        return $mock;
    }

    public function tearDown()
    {
        Mockery::close();
    }
}