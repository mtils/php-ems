<?php

namespace Ems;

use Mockery;
use PHPUnit\Framework\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
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