<?php

namespace Ems\Core\Filesystem;

use Ems\Contracts\Core\Filesystem as FSContract;
use Ems\Core\LocalFilesystem;
use Ems\Testing\FilesystemMethods;

use Iterator;

class LineReadIteratorTest extends \Ems\IntegrationTest
{

    public function test_implements_interface()
    {
        $this->assertInstanceOf(
            Iterator::class,
            $this->newReader()
        );
    }

    public function test_setting_and_getting_chunkSize()
    {
        $reader = $this->newReader();
        $this->assertSame($reader, $reader->setChunkSize(1024));
        $this->assertEquals(1024, $reader->getChunkSize());
    }

    public function test_getting_and_setting_filePath()
    {
        $reader = $this->newReader();
        $this->assertSame($reader, $reader->setFilePath('/tmp/test.bin'));
        $this->assertEquals('/tmp/test.bin', $reader->getFilePath());
    }

    public function test_getting_and_setting_filesystem()
    {
        $filesystem = new LocalFilesystem;
        $reader = $this->newReader('', $filesystem);
        $this->assertSame($filesystem, $reader->getFilesystem());
        $filesystem2 = new LocalFilesystem;
        $this->assertNotSame($filesystem2, $reader->getFilesystem());
        $this->assertSame($reader, $reader->setFilesystem($filesystem2));
        $this->assertSame($filesystem2, $reader->getFilesystem());
    }

    public function test_reads_filled_txt_file()
    {
        $file = $this->dataFile('ascii-data-eol-l.txt');

        $reader = $this->newReader($file)->setChunkSize(1024);

        $lines = [];

        $fileContent = file_get_contents($file);

        $readContent = '';

        foreach ($reader as $i=>$line) {
            $lines[$i] = $line;
        }

        $this->assertCount(11, $lines);
        $this->assertEquals(rtrim($fileContent, "\n"), implode("\n", $lines));
        $this->assertFalse($reader->valid());
        $this->assertEquals(-1, $reader->key());

        // A second time to test reading without re-opening
        $lines2 = [];

        foreach ($reader as $i=>$chunk) {
            $lines2[$i] = $chunk;
        }

        $this->assertCount(11, $lines2);
        $this->assertEquals(rtrim($fileContent, "\n"), implode("\n", $lines2));
        $this->assertFalse($reader->valid());
        $this->assertEquals(-1, $reader->key());

    }

    public function test_count_returns_same_count_as_lines()
    {
        $file = $this->dataFile('ascii-data-eol-l.txt');

        $reader = $this->newReader($file)->setChunkSize(1024);

        $lines = [];

        $fileContent = file_get_contents($file);

        $readContent = '';

        foreach ($reader as $i=>$line) {
            $lines[$i] = $line;
        }

        $this->assertCount(11, $lines);
        $this->assertCount(11, $reader);


    }

    protected function newReader($path='', FSContract $filesystem=null)
    {
        return new LineReadIterator($path, $filesystem);
    }

    public function test_reads_filled_windows_txt_file()
    {
        $file = $this->dataFile('ascii-data-eol-w.txt');

        $reader = $this->newReader($file);

        $lines = [];

        $fileContent = file_get_contents($file);

        $readContent = '';

        foreach ($reader as $i=>$line) {
            $lines[$i] = $line;
        }

        $this->assertCount(11, $lines);
        $this->assertEquals(rtrim($fileContent, "\r\n"), implode("\r\n", $lines));

    }

}
