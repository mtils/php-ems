<?php

namespace Ems\Core\Filesystem;

use Ems\Contracts\Core\Stream;
use Ems\Contracts\Core\Stringable;
use Ems\Testing\FilesystemMethods;
use PHPUnit\Framework\Attributes\Test;

use function file_get_contents;

class StringStreamTest extends \Ems\IntegrationTest
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

    #[Test] public function type_returns_right_type()
    {
        $this->assertEquals('stream', $this->newStream()->type());

        $stream = $this->newStream('Hello');
        $stream->open();
        $this->assertEquals('stream', $stream->type());

    }

    #[Test] public function type_returns_right_type_even_without_resource()
    {
        $this->assertEquals('stream', $this->newStream()->type());

        $stream = $this->newStream('Hello');
        $this->assertEquals('stream', $stream->type());

    }

    #[Test] public function getting_url()
    {
        $stream = $this->newStream();
        $this->assertInstanceOf(\Ems\Contracts\Core\Url::class, $stream->url());
        $this->assertEquals('php://memory', (string)$stream->url());
    }

    #[Test] public function reads_string_in_chunks()
    {
        $file = static::dataFile('ascii-data-eol-l.txt');

        $content = file_get_contents($file);

        $stream = $this->newStream($content)->setChunkSize(1024);

        $chunks = [];

        $readContent = '';

        foreach ($stream as $i=>$chunk) {
            $chunks[$i] = $chunk;
            $readContent .= $chunk;
        }

        $this->assertEquals($content, $readContent);
        $this->assertGreaterThanOrEqual(6, $chunks);
        $this->assertFalse($stream->valid());
        $this->assertEquals(-1, $stream->key());

        // A second time to test reading without re-opening
        $chunks2 = [];
        $readContent2 = '';

        foreach ($stream as $i=>$chunk) {
            $chunks2[$i] = $chunk;
            $readContent2 .= $chunk;
        }

        $this->assertEquals($content, $readContent2);
        $this->assertGreaterThanOrEqual(6, $chunks2);
        $this->assertFalse($stream->valid());
        $this->assertEquals(-1, $stream->key());

    }

    #[Test] public function read_chunk()
    {
        $fileContent = file_get_contents(static::dataFile('ascii-data-eol-l.txt'));

        $stream = $this->newStream($fileContent);
        $content = "$stream";

        $this->assertEquals(substr($content, 0, 1024), $stream->read(1024));

    }

    #[Test] public function reads_in_toString()
    {
        $fileContent = file_get_contents(static::dataFile('ascii-data-eol-l.txt'));

        $stream = $this->newStream($fileContent);

        $this->assertEquals($fileContent, "$stream");

    }

    #[Test] public function reads_empty_string()
    {
        $stream = $this->newStream('')->setChunkSize(1024);

        $i=0;
        foreach ($stream as $chunk) {
            $i++;
        }

        $this->assertEquals(1, $i);
        $this->assertSame('', $chunk);

    }

    #[Test] public function count_returns_strlen()
    {
        $fileContent = static::dataFileContent('ascii-data-eol-l.txt');

        $stream = $this->newStream($fileContent);
        $this->assertCount(strlen($fileContent), $stream);

    }


    #[Test] public function isLocal_returns_correct_value()
    {
        $this->assertTrue($this->newStream()->isLocal());
    }

    #[Test] public function write_file_in_one_row()
    {
        $content = static::dataFileContent('ascii-data-eol-l.txt');

        $stream = $this->newStream('', 'r+');

        $this->assertTrue($stream->write($content));

        $stream->close();

        $this->assertEquals($content, "$stream");

    }

    #[Test] public function write_file_in_chunks()
    {
        $content = static::dataFileContent('ascii-data-eol-l.txt');

        $chunkSize = 256;

        $readStream = $this->newStream($content)->setChunkSize($chunkSize);

        $stream = $this->newStream('', 'a+')->setChunkSize($chunkSize);

        foreach ($readStream as $chunk) {
            $this->assertTrue($stream->write($chunk)); //Didnt work under PHP 7|7.1
            //$stream->write($chunk);
        }

        $stream->close();
        $this->assertEquals($content, "$stream");
    }

    #[Test] public function write_empty_string()
    {
        $stream = $this->newStream('', 'r+');

        $this->assertFalse($stream->write(''));
    }

    /**
     * @param string $string
     * @param string $mode
     *
     * @return StringStream
     */
    protected function newStream($string='', $mode='r+')
    {
        return new StringStream($string, $mode);
    }


}