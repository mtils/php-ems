<?php

namespace Ems\Core\Filesystem;

use Ems\Contracts\Core\Filesystem as FSContract;
use Ems\Contracts\Core\RowContent;
use Ems\Core\LocalFilesystem;
use Ems\Testing\FilesystemMethods;
use Ems\Testing\LoggingCallable;

use Iterator;

class CsvContentTest extends \Ems\IntegrationTest
{

    public function test_implements_interface()
    {
        $this->assertInstanceOf(
            RowContent::class,
            $this->newContent()
        );
    }

    /**
     * @expectedException Ems\Contracts\Core\Errors\Unsupported
     **/
    public function test_rows_throws_exception_if_layers_were_passed()
    {
        $this->newContent()->rows('layer_1');
    }

    public function test_rows_returns_configured_iterator()
    {
        $file = $this->dataFile('simple-pipe-placeholder-no-header.csv');
        $content = $this->newContent($file);

        $iterator = $content->rows();
        $this->assertInstanceOf(CsvReadIterator::class, $iterator);
        $this->assertEquals($file, $iterator->getFilePath());
    }

    protected function newContent($url='', FSContract $filesystem=null)
    {
        return (new CsvContent($filesystem ?: new LocalFilesystem))->setUrl($url);
    }

}
