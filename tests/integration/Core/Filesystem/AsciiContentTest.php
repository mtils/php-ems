<?php

namespace Ems\Core\Filesystem;

use Ems\Contracts\Core\AsciiContent as AsciiContentContract;
use Ems\Contracts\Core\Stream;
use Ems\Core\LocalFilesystem;
use Ems\Testing\LoggingCallable;

class AsciiContentTest extends \Ems\IntegrationTest
{

    public function test_implements_interface()
    {
        $this->assertInstanceOf(
            AsciiContentContract::class,
            $this->newContent()
        );
    }

    public function test_lines_returns_configured_iterator()
    {
        $file = $this->dataFile('ascii-data-eol-l.txt');
        $content = $this->newContent($file);

        $iterator = $content->lines();
        $this->assertInstanceOf(LineReadIterator::class, $iterator);
    }

    public function test_lines_count_returns_lineCount()
    {
        $file = $this->dataFile('ascii-data-eol-l.txt');
        $content = $this->newContent($file);
        $iterator = new LineReadIterator($file);
        $count = count($iterator);
        $this->assertCount($count, $content->lines());
    }

    public function _test_toString_returns_content()
    {
        $file = $this->dataFile('ascii-data-eol-l.txt');
        $content = $this->newContent($file);
        $this->assertEquals(file_get_contents($file), "$content");
    }

    public function _test_getIterator_returns_configured_iterator()
    {
        $file = $this->dataFile('ascii-data-eol-l.txt');
        $content = $this->newContent($file)->setMimeType('text/plain');
        $iterator = $content->getIterator();

        $this->assertInstanceOf(Stream::class, $iterator);
    }

    public function _test_getIterator_creates_iterator_by_custom_callable()
    {
        $file = $this->dataFile('ascii-data-eol-l.txt');
        $fs = new LocalFilesystem;
        $content = $this->newContent($file, $fs)->setMimeType('text/plain');

        $callable = new LoggingCallable(function ($content, $fileSystem) {
            return 'foo';
        });

        $content->createIteratorBy($callable);

        $this->assertEquals('foo', $content->getIterator());

        $this->assertSame($content, $callable->arg(0));
        $this->assertSame($fs, $callable->arg(1));
    }

    protected function newContent($url='', Stream $stream=null)
    {
        $content = new AsciiContent($stream ?: new FileStream($url));
        return $content;
    }

}
