<?php

namespace Ems\Core\Filesystem;

use Countable;
use Ems\Contracts\Core\Stream;
use Ems\IntegrationTest;
use Iterator;

class LineReadIteratorTest extends IntegrationTest
{

    public function test_implements_interfaces()
    {
        $this->assertInstanceOf(
            Iterator::class,
            $this->newReader()
        );
        $this->assertInstanceOf(
            Countable::class,
            $this->newReader()
        );
    }

    public function test_reads_filled_txt_file()
    {
        $file = $this->dataFile('ascii-data-eol-l.txt');

        $reader = $this->newReader($file, $this->stream($file, 1024));

        $lines = [];

        $fileContent = file_get_contents($file);

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

        $reader = $this->newReader($file, $this->stream($file, 1024));

        $lines = [];

        $fileContent = file_get_contents($file);

        $readContent = '';

        foreach ($reader as $i=>$line) {
            $lines[$i] = $line;
        }

        $this->assertCount(11, $lines);
        $this->assertCount(11, $reader);


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

    /**
     * @expectedException \Ems\Contracts\Core\Exceptions\TypeException
     */
    public function test_construct_with_unsupported_parameter()
    {
        new LineReadIterator(15);
    }

    protected function newReader($path='', Stream $stream=null)
    {
        return new LineReadIterator($stream ?: $this->stream($path));
    }

    protected function stream($path, $chunkSize=0)
    {
        $stream = new FileStream($path);
        if ($chunkSize) {
            $stream->setChunkSize($chunkSize);
        }
        return $stream;
    }
}
