<?php

namespace Ems\Core\Filesystem;

use Ems\Contracts\Core\Filesystem as FSContract;
use Ems\Contracts\Core\Stream;
use Ems\Contracts\Core\Stringable;
use Ems\Core\LocalFilesystem;
use Ems\Core\Url;
use Ems\Testing\FilesystemMethods;

use function file_get_contents;
use function filesize;
use Iterator;

class FileStreamTest extends \Ems\IntegrationTest
{
    use FilesystemMethods;

    /**
     * @test
     */
    public function implements_interfaces()
    {
        $stream = $this->newStream();
        $this->assertInstanceOf(
            Stream::class,
            $stream
        );
        $this->assertInstanceOf(
            Stringable::class,
            $stream
        );
    }

    /**
     * @test
     */
    public function setting_and_getting_chunkSize()
    {
        $reader = $this->newStream();
        $this->assertSame($reader, $reader->setChunkSize(1024));
        $this->assertEquals(1024, $reader->getChunkSize());
    }

    /**
     * @test
     */
    public function getting_isReadable()
    {
        $this->assertTrue($this->newStream('/foo', 'r')->isReadable());
        $this->assertFalse($this->newStream('/foo', 'w')->isReadable());
    }

    /**
     * @test
     */
    public function getting_isWritable()
    {
        $this->assertTrue($this->newStream('/foo', 'w')->isWritable());
        $this->assertFalse($this->newStream('/foo', 'r')->isWritable());
    }

    /**
     * @test
     */
    public function test_getting_url()
    {
        $url = new Url('/tmp');
        $stream = $this->newStream($url);
        $this->assertSame($url, $stream->url());
    }

    /**
     * @test
     */
    public function test_isSeekable()
    {
        $this->assertTrue($this->newStream('/dev/null')->open()->isSeekable());
    }

    /**
     * @test
     */
    public function reads_filled_txt_file_in_chunks()
    {
        $file = $this->dataFile('ascii-data-eol-l.txt');

        $stream = $this->newStream($file)->setChunkSize(1024);

        $chunks = [];

        $fileContent = file_get_contents($file);

        $readContent = '';

        foreach ($stream as $i=>$chunk) {
            $chunks[$i] = $chunk;
            $readContent .= $chunk;
        }

        $this->assertEquals($fileContent, $readContent);
        $this->assertCount(6, $chunks);
        $this->assertFalse($stream->valid());
        $this->assertEquals(-1, $stream->key());

        // A second time to test reading without re-opening
        $chunks2 = [];
        $readContent2 = '';

        foreach ($stream as $i=>$chunk) {
            $chunks2[$i] = $chunk;
            $readContent2 .= $chunk;
        }

        $this->assertEquals($fileContent, $readContent2);
        $this->assertCount(6, $chunks2);
        $this->assertFalse($stream->valid());
        $this->assertEquals(-1, $stream->key());

    }

    /**
     * @test
     */
    public function reads_filled_txt_file_in_toString()
    {
        $file = $this->dataFile('ascii-data-eol-l.txt');

        $stream = $this->newStream($file);
        $fileContent = file_get_contents($file);

        $this->assertEquals($fileContent, "$stream");

    }

    /**
     * @test
     * @expectedException \LogicException
     */
    public function read_complete_string_throws_exception_if_write_only()
    {
        $file = $this->dataFile('ascii-data-eol-l.txt');

        $stream = $this->newStream($file, 'w');
        $stream->toString();

    }

    /**
     * @test
     * @expectedException \LogicException
     */
    public function read_string_in_chunks_throws_exception_if_write_only()
    {
        $file = $this->dataFile('ascii-data-eol-l.txt');

        $stream = $this->newStream($file, 'w');

        foreach ($stream as $chunk) {

        }

    }

    /**
     * @test
     * @expectedException \Ems\Contracts\Core\Errors\NotFound
     */
    public function read_throws_exception_if_path_not_found()
    {
        $stream = $this->newStream('/foo');
        $stream->rewind();

    }

    /**
     * @test
     */
    public function open_creates_handle()
    {
        $file = $this->dataFile('ascii-data-eol-l.txt');

        $stream = $this->newStream($file);

        $this->assertFalse($stream->isOpen());
        $this->assertSame($stream, $stream->open());
        $this->assertTrue($stream->isOpen());

    }

    /**
     * @test
     */
    public function seek_moves_cursor()
    {
        $file = $this->dataFile('ascii-data-eol-l.txt');

        $stream = $this->newStream($file)->setChunkSize(128);

        $stream->rewind();

        $beginning = $stream->current();

        $this->assertStringStartsWith('Lorem', $beginning);

        $this->assertStringEndsWith('aliquy', $beginning);

        $stream->seek(128);

        $middle = $stream->current();

        $this->assertStringStartsWith('am erat', $middle);

        $this->assertStringEndsWith('takimata ', $middle);

        $stream->seekEnd();

        $this->assertSame('', $stream->current());

        $this->assertEquals(filesize($file), ftell($stream->resource()));

    }

    /**
     * @test
     */
    public function write_file_in_one_row()
    {
        $inFile = $this->dataFile('ascii-data-eol-l.txt');
        $content = file_get_contents($inFile);

        $outFile = $this->tempFileName();

        $stream = $this->newStream($outFile, 'w');
        $this->assertTrue($stream->write($content));

        $stream->close();

        $this->assertEquals($content, file_get_contents($outFile));

    }

    /**
     * @test
     */
    public function write_file_in_chunks()
    {
        $inFile = $this->dataFile('ascii-data-eol-l.txt');
        $content = file_get_contents($inFile);

        $readStream = $this->newStream($inFile);

        $outFile = $this->tempFile();

        $chunkSize = 256;

        $stream = $this->newStream($outFile, 'a')->setChunkSize($chunkSize);


        foreach ($readStream as $chunk) {
            $this->assertTrue($stream->write($chunk));
        }

        $stream->close();
        $this->assertEquals($content, file_get_contents($outFile));
    }

    /**
     * @test
     */
    public function write_file_by_other_stream()
    {
        $inFile = $this->dataFile('ascii-data-eol-l.txt');
        $content = file_get_contents($inFile);

        $readStream = $this->newStream($inFile);

        $outFile = $this->tempFileName();

        $chunkSize = 256;

        $stream = $this->newStream($outFile, 'w')->setChunkSize($chunkSize);

        $stream->write($readStream);


        $stream->close();
        $this->assertEquals($content, file_get_contents($outFile));

    }

    /**
     * @param string $path
     * @param string $mode
     * @param bool $lock
     *
     * @return FileStream
     */
    protected function newStream($path='/foo', $mode='r+', $lock=false)
    {
        return new FileStream($path, $mode, $lock);
    }

    
}
