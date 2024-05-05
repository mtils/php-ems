<?php

namespace Ems;


use Ems\Core\LocalFilesystem;
use Ems\Skeleton\Application;

/**
 * Use this test if you need a running app
 **/
abstract class LaravelIntegrationTest extends TestCase
{
    use LaravelAppTrait;

    protected static function dataFile($file)
    {
        return realpath(__DIR__."/../data/$file");
    }

    protected function dataFileContent($file)
    {
        return file_get_contents(static::dataFile($file));
    }
}
