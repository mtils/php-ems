<?php


namespace Ems\Core\Laravel;

use Ems\Core\IOCContainerTest as BaseContainerTest;

require_once(__DIR__ . '/../IOCContainerTest.php');

use Ems\Core\ContainerTest_Interface;
use Ems\Core\ContainerTest_Class;
use Ems\Core\ContainerTest_Class2;
use Ems\Core\ContainerTest_ClassDependencies;

class IOCContainerTest extends BaseContainerTest
{

    protected function newContainer()
    {
        $this->preLoadUnloadableClasses();
        return new IOCContainer;
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
