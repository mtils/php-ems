<?php

namespace Ems;


use Ems\Core\LocalFilesystem;
use Ems\Core\Application;

/**
 * Use this test if you need a running app
 **/
class LaravelIntegrationTest extends TestCase
{
    use LaravelAppTrait;

    protected function dataFile($file)
    {
        return realpath(__DIR__."/../data/$file");
    }

    protected function dataFileContent($file)
    {
        return file_get_contents($this->dataFile($file));
    }
}
