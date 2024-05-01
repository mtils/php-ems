<?php

namespace Ems\Core\Filesystem;

use Ems\Contracts\Core\Connection;
use Ems\Contracts\Core\Filesystem as FSContract;
use Ems\Contracts\Core\AsciiContent as AsciiContentContract;
use Ems\Contracts\Core\Url as UrlContract;
use Ems\Core\FilesystemConnection;
use Ems\Core\LocalFilesystem;
use Ems\Core\Url;
use Ems\Testing\FilesystemMethods;
use Ems\Testing\LoggingCallable;

use PHPUnit\Framework\Attributes\Test;
use function is_resource;
use Iterator;

class FilesystemConnectionIntegrationTest extends \Ems\IntegrationTest
{
    use FilesystemMethods;

    #[Test] public function implements_interface()
    {
        $this->assertInstanceOf(
            Connection::class,
            $this->newConnection(new Url())
        );
    }

    #[Test] public function read_returns_file_content()
    {
        $file = __FILE__;

        $fs = $this->newFilesystem();
        $myContent = $fs->read($file);

        $con = $this->newConnection($file);

        $this->assertEquals($myContent, $con->read());

    }

    #[Test] public function write_writes_file_content()
    {
        $file = __FILE__;

        $fs = $this->newFilesystem();
        $myContent = $fs->read($file);

        $tempFile = $this->tempFile();

        $con = $this->newConnection($tempFile);

        $this->assertEquals(strlen($myContent), $con->write($myContent));

        $this->assertEquals($myContent, $con->read());

    }

    #[Test] public function read_in_chunks()
    {
        $file = __FILE__;

        $fs = $this->newFilesystem();
        $myContent = $fs->read($file);

        $con = $this->newConnection($file);

        $content = '';

        while($chunk = $con->read(128)) {
            $content .= $chunk;
        }

        $this->assertEquals($myContent, $content);

        $this->assertTrue(is_resource($con->resource()));

    }

    #[Test] public function open_and_close()
    {
        $file = __FILE__;

        $con = $this->newConnection($file);

        $this->assertFalse($con->isOpen());
        $this->assertSame($con, $con->close());
        $this->assertFalse($con->isOpen());

        $this->assertSame($con, $con->open());
        $this->assertTrue($con->isOpen());
        $this->assertSame($con, $con->close());
        $this->assertFalse($con->isOpen());
    }

    protected function newConnection($url)
    {
       $url = $url instanceof UrlContract ? $url : new Url($url);
       return new FilesystemConnection($url);
    }

}
