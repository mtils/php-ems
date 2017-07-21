<?php

namespace Ems\Core\Filesystem;

use Ems\Contracts\Core\Filesystem as FSContract;
use Ems\Contracts\Core\Content;
use Ems\Core\LocalFilesystem;
use Ems\Testing\FilesystemMethods;
use Ems\Testing\LoggingCallable;

use Iterator;

class BinaryContentTest extends \Ems\IntegrationTest
{

    public function test_implements_interface()
    {
        $this->assertInstanceOf(
            Content::class,
            $this->newContent()
        );
    }

    public function test_getting_and_setting_contentType()
    {
        $content = $this->newContent()->setMimeType('application/pdf');
        $this->assertEquals('application/pdf', $content->mimeType());
    }

    /**
     * @expectedException LogicException
     **/
    public function test_setting_contentType_twice_throws_LogicException()
    {
        $content = $this->newContent()->setMimeType('application/pdf');
        $this->assertEquals('application/pdf', $content->mimeType());
        $content->setMimetype('application/json');

    }

    public function test_setting_contentType_twice_with_same_contentType_does_not_throw_LogicException()
    {
        $content = $this->newContent()->setMimeType('application/pdf');
        $this->assertEquals('application/pdf', $content->mimeType());
        $content->setMimetype('application/pdf');

    }

    public function test_getting_and_setting_url()
    {
        $content = $this->newContent()->setUrl('/home/michi/test.txt');
        $this->assertEquals('/home/michi/test.txt', $content->url());
    }

    /**
     * @expectedException LogicException
     **/
    public function test_setting_url_twice_throws_LogicException()
    {
        $content = $this->newContent()->setUrl('/home/michi/test.txt');
        $this->assertEquals('/home/michi/test.txt', $content->url());
        $content->setUrl('/home/michi/test.log');

    }

    public function test_setting_url_twice_with_same_url_does_not_throw_LogicException()
    {
        $content = $this->newContent('/home/michi/test.txt');
        $this->assertEquals('/home/michi/test.txt', $content->url());
        $content->setUrl('/home/michi/test.txt');

    }

    public function test_count_returns_filesize()
    {
        $file = $this->dataFile('ascii-data-eol-l.txt');
        $content = $this->newContent($file);
        $this->assertCount(filesize($file), $content);
    }

    public function test_toString_returns_content()
    {
        $file = $this->dataFile('ascii-data-eol-l.txt');
        $content = $this->newContent($file);
        $this->assertEquals(file_get_contents($file), "$content");
    }

    public function test_getIterator_returns_configured_iterator()
    {
        $file = $this->dataFile('ascii-data-eol-l.txt');
        $content = $this->newContent($file)->setMimeType('text/plain');
        $iterator = $content->getIterator();

        $this->assertInstanceOf(BinaryReadIterator::class, $iterator);
        $this->assertEquals($file, $iterator->getFilePath());
    }

    public function test_getIterator_creates_iterator_by_custom_callable()
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
        return (new BinaryContent($filesystem ?: new LocalFilesystem))->setUrl($url);
    }

}
