<?php
/**
 *  * Created by mtils on 21.11.2021 at 06:55.
 **/

namespace Ems\Testing\Skeleton;

use Mockery;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;


class TestCase extends PHPUnitTestCase
{
    /**
     * @var string
     */
    protected static $pathToTests = '';

    /**
     * @param string $class
     * @param mixed ...$args
     * @return Mockery\MockInterface
     */
    protected function mock(string $class, ...$args) : Mockery\MockInterface
    {
        return Mockery::mock($class, ...$args);
    }

    /**
     * @after
     */
    protected function tearDownMockery()
    {
        Mockery::close();
    }

}