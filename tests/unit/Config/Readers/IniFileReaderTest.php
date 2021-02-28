<?php
/**
 *  * Created by mtils on 2/28/21 at 8:07 AM.
 **/

namespace Ems\Config\Readers;


use Ems\TestCase;
use Ems\TestData;
use RuntimeException;
use Traversable;

use function iterator_to_array;

use const INI_SCANNER_RAW;
use const INI_SCANNER_TYPED;

class IniFileReaderTest extends TestCase
{
    use TestData;

    /**
     * @test
     */
    public function it_instantiates()
    {
        $reader = $this->make('');
        $this->assertInstanceOf(IniFileReader::class, $reader);
        $this->assertInstanceOf(Traversable::class, $reader);
    }

    /**
     * @test
     */
    public function get_and_set_path()
    {
        $file = $this->dataFile('config/application.ini');
        $reader = $this->make($file);
        $this->assertEquals($file, $reader->getPath());
        $this->assertSame($reader, $reader->setPath('foo'));
        $this->assertEquals('foo', $reader->getPath());
    }

    /**
     * @test
     */
    public function get_and_set_readMode()
    {
        $file = $this->dataFile('config/application.ini');
        $reader = $this->make($file);
        $this->assertEquals(INI_SCANNER_TYPED, $reader->getReadMode());
        $this->assertSame($reader, $reader->setReadMode(INI_SCANNER_RAW));
        $this->assertEquals(INI_SCANNER_RAW, $reader->getReadMode());
    }

    /**
     * @test
     */
    public function get_and_set_processSections()
    {
        $file = $this->dataFile('config/application.ini');
        $reader = $this->make($file);
        $this->assertTrue($reader->shouldProcessSections());
        $this->assertSame($reader, $reader->setProcessSections(false));
        $this->assertFalse($reader->shouldProcessSections());
    }

    /**
     * @test
     */
    public function get_and_set_expandNestedSections()
    {
        $file = $this->dataFile('config/application.ini');
        $reader = $this->make($file);
        $this->assertTrue($reader->shouldExpandNestedSections());
        $this->assertSame($reader, $reader->setExpandNestedSections(false));
        $this->assertFalse($reader->shouldExpandNestedSections());
    }

    /**
     * @test
     */
    public function getIterator_returns_data()
    {
        $reader = $this->make($this->dataFile('config/application.ini'));
        $data = iterator_to_array($reader);
        $this->assertEquals('de', $data['base']['locale']);
        $this->assertEquals('file', $data['cache']['stores']['file']['driver']);
    }

    /**
     * @test
     */
    public function getIterator_returns_data_without_sections()
    {
        $reader = $this->make($this->dataFile('config/application.ini'));
        $reader->setProcessSections(false);
        $data = iterator_to_array($reader);
        $this->assertEquals('en', $data['fallback_locale']);
    }

    /**
     * @test
     */
    public function getIterator_returns_data_without_nested_sections()
    {
        $reader = $this->make($this->dataFile('config/application.ini'));
        $reader->setExpandNestedSections(false);
        $data = iterator_to_array($reader);
        $this->assertEquals('file', $data['cache.stores.file']['driver']);
    }

    /**
     * @test
     */
    public function invalid_file_throws_exception()
    {
        $this->expectException(RuntimeException::class);
        $reader = $this->make('some-not-existing-file.ini');
        $reader->getIterator();
    }

    protected function make($file)
    {
        return new IniFileReader($file);
    }
}