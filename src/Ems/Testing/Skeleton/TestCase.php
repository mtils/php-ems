<?php
/**
 *  * Created by mtils on 21.11.2021 at 06:55.
 **/

namespace Ems\Testing\Skeleton;

use Ems\Testing\Faker\Factory;
use Ems\Testing\Faker\Generator;
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

    /**
     * Create models by faker.
     *
     * @param string $class
     * @param int $quantity
     * @return object|object[]
     */
    protected function fabricate(string $class, int $quantity=1)
    {
        if ($quantity==1) {
            return $this->faker()->instance($class);
        }
        return $this->faker()->instances($class, $quantity);
    }

    /**
     * @param string $class
     * @param int $quantity
     * @return array
     */
    protected function attributes(string $class, int $quantity=1) : array
    {
        $faker = $this->faker();
        if ($quantity == 1) {
            return $faker->attributes($class);
        }

        $rows = [];
        for ($i=0; $i<$quantity; $i++) {
            $rows[] = $faker->attributes($class);
        }
        return $rows;
    }

    /**
     * @return Generator
     */
    protected function faker() : Generator
    {
        $generator = Factory::create();
        $this->addFakerNamespaces($generator);
        return $generator;
    }

    /**
     * @param Generator $generator
     */
    protected function addFakerNamespaces(Generator $generator)
    {
        //
    }

}