<?php
/**
 *  * Created by mtils on 01.01.18 at 12:49.
 **/

namespace Ems;


trait TestData
{
    protected function dataFile($file)
    {
        return realpath(__DIR__."/../../data/$file");
    }

    protected function dataFileContent($file)
    {
        return file_get_contents($this->dataFile($file));
    }
}