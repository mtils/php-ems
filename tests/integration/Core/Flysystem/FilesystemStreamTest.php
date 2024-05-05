<?php
/**
 *  * Created by mtils on 24.10.18 at 08:45.
 **/

namespace Ems\Core\Flysystem;

include_once __DIR__ . '/../Filesystem/FileStreamTest.php';

use Ems\Core\Filesystem\FileStreamTest;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\Filesystem;
use RuntimeException;

class FilesystemStreamTest extends FileStreamTest
{

    #[Test] public function write_file_in_chunks()
    {
        $this->expectException(RuntimeException::class);
        parent::write_file_in_chunks();
    }

    /**
     * @param string $path
     * @param string $mode
     * @param bool $lock
     *
     * @return FilesystemStream
     */
    protected function newStream($path = '/foo', $mode = 'r+', $lock = false)
    {
        $adapter = new LocalFilesystemAdapter('/');
        $fs = new Filesystem($adapter);

        return new FilesystemStream($fs, $path, $mode, $lock);
    }

}