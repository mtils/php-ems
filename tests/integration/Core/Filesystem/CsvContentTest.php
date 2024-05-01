<?php

namespace Ems\Core\Filesystem;

use Ems\Contracts\Core\Errors\UnSupported;
use Ems\Contracts\Core\RowContent;

class CsvContentTest extends \Ems\IntegrationTest
{

    public function test_implements_interface()
    {
        $this->assertInstanceOf(
            RowContent::class,
            $this->newContent()
        );
    }

    public function test_rows_throws_exception_if_layers_were_passed()
    {
        $this->expectException(UnSupported::class);
        $this->newContent()->rows('layer_1');
    }

    public function test_rows_returns_configured_iterator()
    {
        $file = static::dataFile('simple-pipe-placeholder-no-header.csv');
        $content = $this->newContent($file);

        $iterator = $content->rows();
        $this->assertInstanceOf(CsvReadIterator::class, $iterator);
    }

    protected function newContent($url='', Stream $stream=null)
    {
        return (new CsvContent($stream ?: new FileStream($url)))->setUrl($url);
    }

}
