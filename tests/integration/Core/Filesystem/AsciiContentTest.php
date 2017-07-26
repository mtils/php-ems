<?php

namespace Ems\Core\Filesystem;

use Ems\Contracts\Core\Filesystem as FSContract;
use Ems\Contracts\Core\AsciiContent as AsciiContentContract;
use Ems\Core\LocalFilesystem;
use Ems\Testing\FilesystemMethods;
use Ems\Testing\LoggingCallable;

use Iterator;

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
        $this->assertEquals($file, $iterator->getFilePath());
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

        $this->assertInstanceOf(BinaryReadIterator::class, $iterator);
        $this->assertEquals($file, $iterator->getFilePath());
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

    protected function newContent($url='', FSContract $filesystem=null)
    {
        return (new AsciiContent($filesystem ?: new LocalFilesystem))->setUrl($url);
    }

}
