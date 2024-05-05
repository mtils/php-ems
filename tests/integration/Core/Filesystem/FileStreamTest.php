<?php

namespace Ems\Core\Filesystem;

use Ems\Contracts\Core\Stream;
use Ems\Contracts\Core\Stringable;
use Ems\Core\Url;
use Ems\Testing\FilesystemMethods;
use PHPUnit\Framework\Attributes\Test;

use RuntimeException;

use function file_get_contents;
use function filesize;
use function ftell;

class FileStreamTest extends \Ems\IntegrationTest
{
    use FilesystemMethods;

    #[Test] public function implements_interfaces()
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

    #[Test] public function type_returns_right_type_in_AbstractStream()
    {
        $stream = new FileStreamTest_AbstractStream();
        $this->assertEquals('', $stream->type());
    }

    #[Test] public function type_returns_right_type_even_without_resource()
    {
        $this->assertEquals('stream', $this->newStream()->type());

        $file = static::dataFile('ascii-data-eol-l.txt');

        $stream = $this->newStream($file)->setChunkSize(1024);

        $stream->rewind();
        $stream->current();

        $this->assertEquals('stream', $stream->type());

    }

    #[Test] public function methods_that_need_a_stream_also_work_without_one()
    {
        $stream = new FileStreamTest_ResourceLessStream();

        $this->assertSame($stream, $stream->makeAsynchronous());
        $this->assertTrue($stream->isAsynchronous());
        $this->assertFalse($stream->isLocked());
        $this->assertFalse($stream->isTerminalType());
        $this->assertFalse($stream->lock());
        $this->assertFalse($stream->unlock());

        $url = $stream->url();
        $this->assertInstanceOf(\Ems\Contracts\Core\Url::class, $url);
        $this->assertEquals("", "$url");


    }

    #[Test] public function setting_and_getting_chunkSize()
    {
        $reader = $this->newStream();
        $this->assertSame($reader, $reader->setChunkSize(1024));
        $this->assertEquals(1024, $reader->getChunkSize());
    }

    #[Test] public function getting_isReadable()
    {
        $this->assertTrue($this->newStream('/foo', 'r')->isReadable());
        $this->assertFalse($this->newStream('/foo', 'w')->isReadable());
    }

    #[Test] public function getting_isWritable()
    {
        $this->assertTrue($this->newStream('/foo', 'w')->isWritable());
        $this->assertFalse($this->newStream('/foo', 'r')->isWritable());
    }

    #[Test] public function test_getting_url()
    {
        $url = new Url('/tmp');
        $stream = $this->newStream($url);
        $this->assertSame($url, $stream->url());
    }

    #[Test] public function test_isSeekable()
    {
        $this->assertTrue(is_bool($this->newStream('/dev/null')->open()->isSeekable()));
    }

    #[Test] public function reads_filled_txt_file_in_chunks()
    {
        $file = static::dataFile('ascii-data-eol-l.txt');

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

    #[Test] public function read_chunk()
    {
        $file = static::dataFile('ascii-data-eol-l.txt');

        $stream = $this->newStream($file);
        $content = "$stream";

        $this->assertEquals(substr($content, 0, 1024), $stream->read(1024));

    }

    #[Test] public function reads_filled_txt_file_in_toString()
    {
        $file = static::dataFile('ascii-data-eol-l.txt');

        $stream = $this->newStream($file);
        $fileContent = file_get_contents($file);

        $this->assertEquals($fileContent, "$stream");

    }

    #[Test] public function reads_empty_file()
    {
        $file = static::dataFile('empty.txt');

        $stream = $this->newStream($file)->setChunkSize(1024);

        $i=0;
        foreach ($stream as $chunk) {
            $i++;
        }

        $this->assertEquals(1, $i);
        $this->assertSame('', $chunk);

    }

    #[Test] public function test_read_complete_string_throws_exception_if_write_only()
    {
        $this->expectException(\LogicException::class);
        $file = static::dataFile('ascii-data-eol-l.txt');

        $stream = $this->newStream($file, 'w');
        $stream->toString();

    }

    #[Test] public function read_string_in_chunks_throws_exception_if_write_only()
    {
        $this->expectException(\LogicException::class);
        $file = static::dataFile('ascii-data-eol-l.txt');

        $stream = $this->newStream($file, 'w');

        foreach ($stream as $chunk) {

        }

    }

    #[Test] public function read_throws_exception_if_path_not_found()
    {
        $this->expectException(\Ems\Contracts\Core\Errors\NotFound::class);
        $stream = $this->newStream('/foo');
        $stream->rewind();

    }

    #[Test] public function count_returns_filesize()
    {
        $file = static::dataFile('ascii-data-eol-l.txt');

        $stream = $this->newStream($file);
        $this->assertCount(filesize($file), $stream);

    }

    #[Test] public function open_creates_handle()
    {
        $file = static::dataFile('ascii-data-eol-l.txt');

        $stream = $this->newStream($file);

        $this->assertFalse($stream->isOpen());
        $this->assertSame($stream, $stream->open());
        $this->assertTrue($stream->isOpen());

    }

    #[Test] public function seek_moves_cursor()
    {
        $file = static::dataFile('ascii-data-eol-l.txt');

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

    #[Test] public function seek_throws_exception_if_resource_not_seekable()
    {
        $this->expectException(\Ems\Contracts\Core\Errors\UnSupported::class);
        (new FileStreamTest_ResourceLessStream())->seek(10);
    }

    #[Test] public function metaData_returns_data()
    {
        $file = static::dataFile('ascii-data-eol-l.txt');

        $stream = $this->newStream($file)->setChunkSize(128);

        $stream->rewind();

        $stream->current();

        $metaData = $stream->metaData();
        $uri = $stream->metaData('uri');
        $this->assertEquals($metaData['uri'], $uri);

        $stream2 = new FileStreamTest_AbstractStream();
        $stream2->setResource(null);

    }

    #[Test] public function mode_returns_mode()
    {
        $stream = new FileStreamTest_ResourceLessStream();
        $this->assertEquals('r+', $stream->mode());

    }

    #[Test] public function isLocal_returns_correct_value_is_no_resource_present()
    {

        $stream = new FileStreamTest_ResourceLessStream();
        $stream->url = new Url('file:///tmp/test.txt');

        $this->assertTrue($stream->isLocal());

        $stream = new FileStreamTest_ResourceLessStream();
        $stream->url = new Url('https://www.google.de');

        $this->assertFalse($stream->isLocal());

        $stream = new FileStreamTest_ResourceLessStream();
        $stream->url = null;
        $this->assertFalse($stream->isLocal());

    }

    #[Test] public function write_file_in_one_row()
    {
        $inFile = static::dataFile('ascii-data-eol-l.txt');
        $content = file_get_contents($inFile);

        $outFile = $this->tempFileName();

        $stream = $this->newStream($outFile, 'w');
        $this->assertTrue($stream->write($content));

        $stream->close();

        $this->assertEquals($content, file_get_contents($outFile));

    }

    #[Test] public function write_file_in_chunks()
    {
        $inFile = static::dataFile('ascii-data-eol-l.txt');
        $content = file_get_contents($inFile);

        $readStream = $this->newStream($inFile);

        $outFile = $this->tempFile();

        $chunkSize = 256;

        $stream = $this->newStream($outFile, 'a')->setChunkSize($chunkSize);


        $this->expectException(RuntimeException::class);
        foreach ($readStream as $chunk) {
            $this->assertTrue($stream->write($chunk));
        }

    }

    #[Test] public function write_file_by_other_stream()
    {
        $inFile = static::dataFile('ascii-data-eol-l.txt');
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

class FileStreamTest_AbstractStream extends AbstractStream
{
    public $url = false;

    /**
     * @return Url
     */
    public function url()
    {
        if ($this->url !== false) {
            return $this->url;
        }
        return parent::url();
    }


    /**
     * @param resource $resource
     * @return $this
     */
    public function setResource($resource)
    {
        $this->resource = $resource;
        return $this;
    }
}

class FileStreamTest_ResourceLessStream extends FileStreamTest_AbstractStream
{


    /**
     * @return resource
     */
    public function resource()
    {
        return null;
    }

}