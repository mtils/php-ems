<?php
/**
 *  * Created by mtils on 24.10.18 at 08:45.
 **/

namespace Ems\Core\Flysystem;

include_once __DIR__ . '/../Filesystem/FileStreamTest.php';

use Ems\Core\Filesystem\FileStreamTest;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;

class FilesystemStreamTest extends FileStreamTest
{
    /**
     * @param string $path
     * @param string $mode
     * @param bool $lock
     *
     * @return FilesystemStream
     */
    protected function newStream($path = '/foo', $mode = 'r+', $lock = false)
    {
        $adapter = new Local('/');
        $fs = new Filesystem($adapter);

        return new FilesystemStream($fs, $path, $mode, $lock);
    }

}