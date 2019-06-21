<?php

namespace Ems\Core\Filesystem;

use Countable;
use Ems\Contracts\Core\Filesystem as FSContract;
use Ems\Contracts\Core\StringConverter;
use Iterator;
use Ems\IntegrationTest;

class CsvReadIteratorTest extends IntegrationTest
{

    public function test_implements_interfaces()
    {
        $this->assertInstanceOf(
            Iterator::class,
            $this->newReader()
        );
        $this->assertInstanceOf(
            Countable::class,
            $this->newReader()
        );
    }

    public function test_getSeparator_detects_separator_if_non_setted()
    {
        $reader = $this->newReader($this->dataFile('simple-pipe-placeholder-normalized.csv'));
        $this->assertEquals('|', $reader->getSeparator());
    }

    public function test_getSeparator_returns_setted_separator()
    {
        $reader = $this->newReader($this->dataFile('simple-pipe-placeholder-normalized.csv'));
        $this->assertSame($reader, $reader->setSeparator(';'));
        $this->assertEquals(';', $reader->getSeparator());
    }

    public function test_setDelimiter_sets_delimiter()
    {
        $reader = $this->newReader($this->dataFile('simple-pipe-placeholder-normalized.csv'));
        $this->assertSame($reader, $reader->setDelimiter("'"));
        $this->assertEquals("'", $reader->getDelimiter());
    }

    public function test_getDetector_returns_Detector()
    {
        $this->assertInstanceOf(CsvDetector::class, $this->newReader()->getDetector());
    }

    public function test_getHeader_detects_header_if_not_setted()
    {
        $reader = $this->newReader($this->dataFile('simple-pipe-placeholder-normalized.csv'));
        $this->assertEquals(
            ['id', 'name', 'last_name', 'age', 'street'],
            $reader->getHeader()
        );
    }

    public function test_getHeader_returns_setted_header()
    {
        $reader = $this->newReader();
        $this->assertSame($reader, $reader->setHeader(['id', 'name']));
        $this->assertEquals(['id', 'name'], $reader->getHeader());
    }

    public function test_getStringConverter_returns_converter()
    {
        $reader = $this->newReader();
        $this->assertInstanceOf(StringConverter::class, $reader->getStringConverter());
    }

    public function test_read_simple_csv_file()
    {
        $reader = $this->newReader($this->dataFile('simple-pipe-placeholder-normalized.csv'));

        $result = [];

        foreach ($reader as $row) {
            $result[] = $row;
        }

        $awaited = [
            [
                'id'        => '42',
                'name'      => 'Talent',
                'last_name' => 'Billy',
                'age'       => '35',
                'street'    => 'Elm Street'
            ],
            [
                'id'        => '52',
                'name'      => 'Duck',
                'last_name' => 'Donald',
                'age'       => '8',
                'street'    => 'Duckcity'
            ]
        ];
        $this->assertEquals($awaited, $result);

    }

    public function test_read_simple_csv_file_when_no_header_setted()
    {
        $reader = $this->newReader($this->dataFile('simple-pipe-placeholder-no-header.csv'));

        $result = [];

        foreach ($reader as $row) {
            $result[] = $row;
        }

        $awaited = [
            [
                0        => '42',
                1      => 'Talent',
                2 => 'Billy',
                3       => '35',
                4    => 'Elm Street'
            ],
            [
                0        => '52',
                1      => 'Duck',
                2   => 'Donald',
                3       => '8',
                4    => 'Duckcity'
            ]
        ];

        $this->assertEquals($awaited, $result);

    }

    public function test_read_country_csv_file()
    {
        $reader = $this->newReader($this->dataFile('Countries-ISO-3166-2.csv'));

        $firstRowShouldBe = [
            'Sort Order'               => '1',
            'Common Name'              => 'Afghanistan',
            'Formal Name'              => 'Islamic State of Afghanistan',
            'Type'                     => 'Independent State',
            'Sub Type'                 => '',
            'Sovereignty'              => '',
            'Capital'                  => 'Kabul',
            'ISO 4217 Currency Code'   => 'AFN',
            'ISO 4217 Currency Name'   => 'Afghani',
            'ITU-T Telephone Code'     => '93',
            'ISO 3166-1 2 Letter Code' => 'AF',
            'ISO 3166-1 3 Letter Code' => 'AFG',
            'ISO 3166-1 Number'        => '4',
            'IANA Country Code TLD'    => '.af'
        ];

        $lastRowShouldBe = [
            'Sort Order'               => '272',
            'Common Name'              => 'British Antarctic Territory',
            'Formal Name'              => '',
            'Type'                     => 'Antarctic Territory',
            'Sub Type'                 => 'Overseas Territory',
            'Sovereignty'              => 'United Kingdom',
            'Capital'                  => '',
            'ISO 4217 Currency Code'   => '',
            'ISO 4217 Currency Name'   => '',
            'ITU-T Telephone Code'     => '',
            'ISO 3166-1 2 Letter Code' => 'AQ',
            'ISO 3166-1 3 Letter Code' => 'ATA',
            'ISO 3166-1 Number'        => '10',
            'IANA Country Code TLD'    => '.aq'
        ];

        $firstRow = [];

        foreach ($reader as $i=>$row) {
            if ($i==0) {
                $firstRow = $row;
            }
        }

        $this->assertEquals($firstRowShouldBe, $firstRow);
        $this->assertEquals($lastRowShouldBe, $row);
        $this->assertEquals(268, $i+1);

    }

    public function test_read_country_csv_file_with_skippable_lines()
    {
        $reader = $this->newReader($this->dataFile('Countries-ISO-3166-2-semicolon-blank-lines.csv'));

        $firstRowShouldBe = [
            'Sort Order'               => '1',
            'Common Name'              => 'Afghanistan',
            'Formal Name'              => 'Islamic State of Afghanistan',
            'Type'                     => 'Independent State',
            'Sub Type'                 => '',
            'Sovereignty'              => '',
            'Capital'                  => 'Kabul',
            'ISO 4217 Currency Code'   => 'AFN',
            'ISO 4217 Currency Name'   => 'Afghani',
            'ITU-T Telephone Code'     => '93',
            'ISO 3166-1 2 Letter Code' => 'AF',
            'ISO 3166-1 3 Letter Code' => 'AFG',
            'ISO 3166-1 Number'        => '4',
            'IANA Country Code TLD'    => '.af'
        ];

        $lastRowShouldBe = [
            'Sort Order'               => '272',
            'Common Name'              => 'British Antarctic Territory',
            'Formal Name'              => '',
            'Type'                     => 'Antarctic Territory',
            'Sub Type'                 => 'Overseas Territory',
            'Sovereignty'              => 'United Kingdom',
            'Capital'                  => '',
            'ISO 4217 Currency Code'   => '',
            'ISO 4217 Currency Name'   => '',
            'ITU-T Telephone Code'     => '',
            'ISO 3166-1 2 Letter Code' => 'AQ',
            'ISO 3166-1 3 Letter Code' => 'ATA',
            'ISO 3166-1 Number'        => '10',
            'IANA Country Code TLD'    => '.aq'
        ];

        $firstRow = [];

        foreach ($reader as $i=>$row) {
            if ($i==0) {
                $firstRow = $row;
            }
        }

        $this->assertEquals($firstRowShouldBe, $firstRow);
        $this->assertEquals($lastRowShouldBe, $row);
        $this->assertEquals(268, $i+1);

    }

    public function test_read_with_different_encoding()
    {
        $reader = $this->newReader($this->dataFile('simple-semicolon-placeholder-iso.csv'));

        $reader->setOption('encoding', 'iso-8859-1');

        $this->assertEquals(
            ['id', 'Völlig bekloppte Spalte (nur so)', 'last_name', 'age', 'street'],
            $reader->getHeader()
        );

        $awaited = [
            [
                'id'                               => '42',
                'Völlig bekloppte Spalte (nur so)' => 'Talent',
                'last_name'                        => 'Ängelbärt',
                'age'                              => '35',
                'street'                           => 'Elm Street'
            ],
            [
                'id'                               => '52',
                'Völlig bekloppte Spalte (nur so)' => 'Duck',
                'last_name'                        => 'Tönjes',
                'age'                              => '8',
                'street'                           => 'Duckcity'
            ]
        ];

        $result = [];

        foreach ($reader as $row) {
            $result[] = $row;
        }

        $this->assertEquals($awaited, $result);

    }

    /**
     * @expectedException Ems\Core\Exceptions\InvalidCharsetException
     **/
    public function test_read_with_wrong_encoding_throws_InvalidCharsetException()
    {

        $reader = $this->newReader($this->dataFile('simple-semicolon-placeholder-iso.csv'));
        $reader->setDetector((new CsvDetector)->setOption(CsvDetector::FORCE_HEADER_LINE, true));

        $this->assertEquals(
            ['id', 'Völlig bekloppte Spalte (nur so)', 'last_name', 'age', 'street'],
            $reader->getHeader()
        );

        $awaited = [
            [
                'id'                               => '42',
                'Völlig bekloppte Spalte (nur so)' => 'Talent',
                'last_name'                        => 'Ängelbärt',
                'age'                              => '35',
                'street'                           => 'Elm Street'
            ],
            [
                'id'                               => '52',
                'Völlig bekloppte Spalte (nur so)' => 'Duck',
                'last_name'                        => 'Tönjes',
                'age'                              => '8',
                'street'                           => 'Duckcity'
            ]
        ];

        $result = [];

        foreach ($reader as $row) {
            $result[] = $row;
        }

        $this->assertEquals($awaited, $result);

    }

    /**
     * @expectedException \Ems\Core\Exceptions\DetectionFailedException
     **/
    public function test_read_undetectable_header_throws_DetectionFailedException()
    {

        $reader = $this->newReader($this->dataFile('simple-pipe-placeholder-no-header.csv'));
        $reader->setDetector((new CsvDetector)->setOption(CsvDetector::FORCE_HEADER_LINE, true));

        $reader->getHeader();

    }

    public function test_count_returns_count_of_simple_file()
    {
        $reader = $this->newReader($this->dataFile('simple-pipe-placeholder-normalized.csv'));
        $this->assertCount(2, $reader);

    }

    public function test_count_returns_count_of_simple_file_without_header()
    {
        $reader = $this->newReader($this->dataFile('simple-pipe-placeholder-no-header.csv'));
        $this->assertCount(2, $reader);

    }

    public function test_count_returns_count_of_country_file()
    {
        $reader = $this->newReader($this->dataFile('Countries-ISO-3166-2.csv'));
        $this->assertCount(268, $reader);
    }

    public function test_count_returns_count_of_country_file_with_skippable_lines()
    {
        $reader = $this->newReader($this->dataFile('Countries-ISO-3166-2-semicolon-blank-lines.csv'));
        $this->assertCount(268, $reader);
    }

    protected function newReader($path='')
    {
        return new CsvReadIterator($path);
    }

}
