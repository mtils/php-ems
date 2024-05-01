<?php

namespace Ems\Core\Filesystem;

use Ems\Contracts\Core\Filesystem as FSContract;
use Ems\Contracts\Core\Stream;
use Ems\Contracts\Core\Stringable;
use Ems\Core\LocalFilesystem;
use Ems\Core\Url;
use Ems\Testing\FilesystemMethods;

use PHPUnit\Framework\Attributes\Test;
use function file_get_contents;
use function filesize;
use function is_resource;
use Iterator;
use const LOCK_EX;
use function stream_get_meta_data;
use function var_dump;

class ResourceStreamTest extends \Ems\IntegrationTest
{
    use FilesystemMethods;

    #[Test] public function implements_interfaces()
    {
        $stream = $this->newStream();
        $this->assertInstanceOf(
            Stream::class,
            $stream
        );
    }

    #[Test] public function setting_and_getting_chunkSize()
    {
        $reader = $this->newStream();
        $this->assertSame($reader, $reader->setChunkSize(1024));
        $this->assertEquals(1024, $reader->getChunkSize());
    }

    #[Test] public function getting_isReadable()
    {
        $tempFile = $this->tempFile();

        $resource = fopen($tempFile, 'r');

        $this->assertTrue($this->newStream($resource)->isReadable());
        @fclose($resource);

        $resource = fopen($tempFile, 'w');

        $this->assertFalse($this->newStream($resource)->isReadable());
        @fclose($resource);

        $resource = fopen($tempFile, 'a+');

        $this->assertTrue($this->newStream($resource)->isReadable());
        @fclose($resource);

    }

    #[Test] public function getting_isWritable()
    {
        $tempFile = $this->tempFile();

        $resource = fopen($tempFile, 'r');

        $this->assertFalse($this->newStream($resource)->isWritable());
        @fclose($resource);

        $resource = fopen($tempFile, 'w');

        $this->assertTrue($this->newStream($resource)->isWritable());
        @fclose($resource);

        $resource = fopen($tempFile, 'a+');

        $this->assertTrue($this->newStream($resource)->isWritable());
        @fclose($resource);
    }

    #[Test] public function getting_isAsynchronous()
    {

        $tempFile = $this->tempFile();

        $resource = fopen($tempFile, 'r');

        $this->assertFalse($this->newStream($resource)->isAsynchronous());

        @fclose($resource);

        $resource = fopen($tempFile, 'r');
        $stream = $this->newStream($resource);

        $this->assertFalse($stream->isAsynchronous());

        $this->assertSame($stream, $stream->makeAsynchronous(true));

        $this->assertTrue($stream->isAsynchronous());

        @fclose($resource);

        $resource = fopen('data://text/plain;base64,', 'r');

        $this->assertTrue($this->newStream($resource)->isAsynchronous());

        @fclose($resource);
    }

    #[Test] public function getting_url()
    {

        $tempFile = $this->tempFile();

        $resource = fopen($tempFile, 'r');

        $stream = $this->newStream($resource);

        $url = $stream->url();

        $this->assertInstanceOf(Url::class, $url);

        $this->assertEquals($tempFile,"$url");

        @fclose($resource);

    }

    #[Test] public function isSeekable()
    {
        $tempFile = $this->tempFile();

        $resource = fopen($tempFile, 'r');

        $stream = $this->newStream($resource);

        $this->assertTrue($stream->isSeekable());

        @fclose($resource);



        $resource = fopen('php://stdin', 'r');

        $stream = $this->newStream($resource);

        $this->assertFalse($stream->isSeekable());

        @fclose($resource);

    }

    #[Test] public function reads_filled_txt_file_in_chunks()
    {
        $file = static::dataFile('ascii-data-eol-l.txt');

        $resource = fopen($file, 'r');

        $stream = $this->newStream($resource)->setChunkSize(1024);

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

    #[Test] public function reads_filled_txt_file_in_toString()
    {
        $file = static::dataFile('ascii-data-eol-l.txt');
        $resource = fopen($file, 'r');

        $stream = $this->newStream($resource);
        $fileContent = file_get_contents($file);

        $this->assertEquals($fileContent, "$stream");

    }

    #[Test] public function instantiate_with_non_resource_throws_TypeException()
    {
        $this->expectException(
            \Ems\Contracts\Core\Exceptions\TypeException::class
        );
        $file = static::dataFile('ascii-data-eol-l.txt');

        $this->newStream($file, false);
    }

    #[Test] public function isOpen_returns_right_state()
    {

        $file = static::dataFile('ascii-data-eol-l.txt');
        $resource = fopen($file, 'r');

        $stream = $this->newStream($resource);

        $this->assertTrue(is_resource($stream->resource()));

        $this->assertSame($stream, $stream->open());
        $this->assertTrue($stream->isOpen());
        $this->assertSame($stream, $stream->close());
        $this->assertFalse($stream->isOpen());

    }

    #[Test] public function seek_moves_cursor()
    {
        $file = static::dataFile('ascii-data-eol-l.txt');
        $resource = fopen($file, 'r');

        $stream = $this->newStream($resource)->setChunkSize(128);

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

    #[Test] public function write_file_in_one_row()
    {
        $inFile = static::dataFile('ascii-data-eol-l.txt');

        $content = file_get_contents($inFile);

        $outFile = $this->tempFile();

        $resource = fopen($outFile, 'w');

        $stream = $this->newStream($resource);

        $this->assertEquals(strlen($content), $stream->write($content));

        $stream->close();

        $this->assertEquals($content, file_get_contents($outFile));

    }

    #[Test] public function write_file_in_chunks()
    {
        $inFile = static::dataFile('ascii-data-eol-l.txt');
        $content = file_get_contents($inFile);

        $readStream = $this->newStream(fopen($inFile, 'r'));

        $outFile = $this->tempFile();
        $resource = fopen($outFile, 'w');

        $chunkSize = 256;

        $stream = $this->newStream($resource)->setChunkSize($chunkSize);


        foreach ($readStream as $chunk) {
            $this->assertEquals(strlen($chunk), $stream->write($chunk));
        }

        $stream->close();
        $this->assertEquals($content, file_get_contents($outFile));
    }

    #[Test] public function write_file_by_other_stream()
    {
        $inFile = static::dataFile('ascii-data-eol-l.txt');
        $content = file_get_contents($inFile);

        $inFileResource = fopen($inFile, 'r');

        $readStream = $this->newStream($inFileResource);

        $outFile = $this->tempFile();

        $outFileResource = fopen($outFile, 'w');

        $chunkSize = 256;

        $stream = $this->newStream($outFileResource)->setChunkSize($chunkSize);

        $stream->write($readStream);


        $stream->close();
        $this->assertEquals($content, file_get_contents($outFile));

    }

    #[Test] public function isLocal()
    {
        $inFile = static::dataFile('ascii-data-eol-l.txt');
        $inFileResource = fopen($inFile, 'r');

        $stream = $this->newStream($inFileResource);

        $this->assertTrue($stream->isLocal());

        $stream->close();


    }

    #[Test] public function setTimeout()
    {
        $inFile = static::dataFile('ascii-data-eol-l.txt');
        $inFileResource = fopen($inFile, 'r');

        $stream = $this->newStream($inFileResource);

        $this->assertSame($stream, $stream->setTimeout(500));

        $stream->close();


    }

    #[Test] public function type()
    {
        $inFile = static::dataFile('ascii-data-eol-l.txt');
        $inFileResource = fopen($inFile, 'r');

        $stream = $this->newStream($inFileResource);

        $this->assertEquals('stream', $stream->type());

        $stream->close();


    }

    #[Test] public function isTerminalType()
    {
        $inFile = static::dataFile('ascii-data-eol-l.txt');
        $inFileResource = fopen($inFile, 'r');

        $stream = $this->newStream($inFileResource);

        $this->assertFalse($stream->isTerminalType());

        $stream->close();


    }

    #[Test] public function write_throws_exception_if_not_writable()
    {
        $this->expectException(\LogicException::class);
        $outFile = $this->tempFile();
        $this->newStream($outFile, true, false)->write('whatever');
    }

    #[Test] public function lock_and_unlock_file()
    {

        $file = $this->tempFile();
        $resource = fopen($file, 'r+');

        $stream = $this->newStream($resource);

        $this->assertFalse($stream->isLocked());
        $this->assertTrue($stream->lock(true));
        $this->assertTrue($stream->isLocked());
        $this->assertTrue($stream->unlock());
        $this->assertFalse($stream->isLocked());

    }

    /**
     * @param resource $resource
     *
     * @return ResourceStream
     */
    protected function newStream($resource=null)
    {
        $uri = 'data://text/plain;base64,';
        return new ResourceStream($resource ?: fopen($uri, 'r'));
    }


}
