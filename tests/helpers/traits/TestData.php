<?php
/**
 *  * Created by mtils on 01.01.18 at 12:49.
 **/

namespace Ems;


trait TestData
{
    /**
     * Return the full path to a data file.
     *
     * @param string $file
     * @return bool|string
     */
    protected static function dataFile($file)
    {
        return realpath(__DIR__."/../../data/$file");
    }

    /**
     * Get the contents of a data file
     *
     * @param string $file
     *
     * @return false|string
     */
    protected static function dataFileContent($file)
    {
        return file_get_contents(static::dataFile($file));
    }

    /**
     * @param string $file
     *
     * @return array
     */
    protected static function includeDataFile($file)
    {
        /** @noinspection PhpIncludeInspection */
        return include(static::dataFile($file));
    }


}