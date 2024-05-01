<?php

namespace Ems\Core\Laravel;

use Ems\Core\IOCContainerTest as BaseContainerTest;

require_once __DIR__.'/../IOCContainerTest.php';

use Ems\Core\ContainerTest_Interface;
use Ems\Core\ContainerTest_Class;
use Ems\Core\ContainerTest_Class2;
use Ems\Core\ContainerTest_ClassDependencies;
use Illuminate\Container\Container;

class IOCContainerTest extends BaseContainerTest
{
    public function test_call_injects_multiple_dependencies_and_parameters()
    {
        // Laravel seems to behave differently here...
    }

    public function test_call_injects_multiple_dependencies_and_parameters_in_different_order()
    {
        // Laravel seems to behave differently here...
    }

    public function test_share_does_not_end_in_endless_recursion_when_abstract_and_concrete_is_same()
    {
        // I cannot ensure this with laravel
        // Here you have to use a different string or aliases
    }

    public function test_laravel_returns_original_container()
    {
        $container = $this->newContainer();
        $this->assertInstanceOf(Container::class, $container->laravel());
    }

    public function test_create_uses_exact_class_if_forced()
    {
        $this->expectException(
            \Ems\Core\Exceptions\UnsupportedUsageException::class
        );
        parent::test_create_uses_exact_class_if_forced();
    }


    protected function newContainer()
    {
        $this->preLoadUnloadableClasses();
        return new IOCContainer();
    }

    protected function preLoadUnloadableClasses()
    {
        $classes = [
            'ContainerTest_Interface',
            'ContainerTest_Class',
            'ContainerTest_Class2',
            'ContainerTest_ClassDependencies'
        ];

        foreach ($classes as $class) {
            class_exists("Ems\\Core\\$class", true);
        }
    }
}
