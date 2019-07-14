<?php

namespace Ems\Core\Filesystem;

use Ems\Testing\Cheat;

class CsvDetectorTest extends \Ems\IntegrationTest
{

    protected $csvContents = [];

    public function test_separator_returns_right_separator_with_simple_format()
    {
        $csv = static::dataFileContent('simple-pipe-placeholder.csv');

        $detector = $this->newDetector();

        foreach ([',', ';', "\t", '|', '^'] as $separator) {
            $test = str_replace(' | ', $separator, $csv);
            $this->assertEquals($separator, $detector->separator($test));
        }
    }

    public function test_separator_returns_right_separator_when_separator_inside_column()
    {

        $csv = $this->csvContent('simple-pipe-placeholder.csv');
        $csv = str_replace(',last_name,',',"last,name",',$csv);

        $detector = $this->newDetector();

        $this->assertEquals(',', $detector->separator($csv));

    }

    /**
     * @expectedException Ems\Core\Exceptions\DetectionFailedException
     **/
    public function test_separator_throws_exception_if_no_newlines_found()
    {
        $detector = $this->newDetector();
        $this->assertEquals(',', $detector->separator(''));
    }

    /**
     * @expectedException Ems\Core\Exceptions\DetectionFailedException
     **/
    public function test_separator_throws_exception_if_column_count_does_not_match()
    {
        $csv = $this->csvContent('simple-pipe-placeholder.csv');

        // Original file has 5 cols
        $csv .= "\n" . 'a,b,c,d,e,f';

        $detector = $this->newDetector();

        $detector->separator($csv);

    }

    public function test_separator_returns_empty_string_if_no_columns_found()
    {
        $csv = "a\nb\nc\nd\ne\nf\ng\nh\ni";

        $detector = $this->newDetector();

        $this->assertEquals('', $detector->separator($csv));
    }

    public function test_getSeparators_returns_separators()
    {
        $detector = $this->newDetector();
        $this->assertEquals($detector->getSeparators(), Cheat::get($detector, 'separators'));
    }

    public function test_addSeparator_adds_separator()
    {
        $detector = $this->newDetector();
        $this->assertFalse(in_array('*', $detector->getSeparators()));
        $this->assertSame($detector, $detector->addSeparator('*'));
        $this->assertTrue(in_array('*', $detector->getSeparators()));
    }

    public function test_header_returns_header_of_simple_file()
    {
        $detector = $this->newDetector();
        $csv = $this->csvContent('simple-pipe-placeholder.csv');
        $header = ['id', 'name', 'last_name', 'age', 'street'];
        $this->assertEquals($header, $detector->header($csv, ','));
    }

    public function test_header_returns_empty_header_of_simple_file()
    {
        $detector = $this->newDetector();
        $csv = $this->csvContent('simple-pipe-placeholder.csv');

        // Remove first line
        $lines = explode("\n", $csv);
        array_shift($lines);
        $csv = implode("\n", $lines);

        $header = range(0, 4);
        $this->assertEquals($header, $detector->header($csv, ','));
    }

    public function test_header_returns_header_umlaut_columns()
    {
        $detector = $this->newDetector();
        $firstLine = 'id,e-Mail,Ärmelgröße,Hat unterdrückt,telefon geschäftlich';
        $secondLine = '42,me@to.de,45.67,Nein,0721 187744657';

        $shouldBe = explode(',', $firstLine);

        $this->assertEquals($shouldBe, $detector->header("$firstLine\n$secondLine", ','));
    }

    public function test_header_returns_empty_header_with_numeric_columns()
    {
        $detector = $this->newDetector();
        $firstLine = 'id,e-Mail,Ärmelgröße,32,telefon geschäftlich';
        $secondLine = '42,me@to.de,45.67,Nein,0721 187744657';

        $this->assertEquals(range(0,4), $detector->header("$firstLine\n$secondLine", ','));
    }

    public function test_header_returns_empty_header_if_columns_not_unique()
    {
        $detector = $this->newDetector();
        $firstLine = 'id,e-Mail,Ärmelgröße,e-Mail,telefon geschäftlich';
        $secondLine = '42,me@to.de,45.67,Nein,0721 187744657';

        $this->assertEquals(range(0,4), $detector->header("$firstLine\n$secondLine", ','));
    }

    public function test_header_returns_empty_header_if_empty_column_found()
    {
        $detector = $this->newDetector();
        $firstLine = 'id,e-Mail,Ärmelgröße,,telefon geschäftlich';
        $secondLine = '42,me@to.de,45.67,Nein,0721 187744657';

        $this->assertEquals(range(0,4), $detector->header("$firstLine\n$secondLine", ','));
    }

    // Sometimes I hate spreadsheet programs...
    public function test_header_returns_correct_header_if_empty_column_found_at_end()
    {
        $detector = $this->newDetector();
        $firstLine = 'id,e-Mail,Ärmelgröße,Vermählt,telefon geschäftlich,';
        $secondLine = '42,me@to.de,45.67,Nein,0721 187744657,';

        $awaited = explode(',', $firstLine);

        $this->assertEquals($awaited, $detector->header("$firstLine\n$secondLine", ','));
    }

    public function test_header_returns_empty_header_if_columns_without_letters_found()
    {
        $detector = $this->newDetector();
        $firstLine = 'id,e-Mail,Ärmelgröße,-_01-=,telefon geschäftlich';
        $secondLine = '42,me@to.de,45.67,Nein,0721 187744657';

        $this->assertEquals(range(0,4), $detector->header("$firstLine\n$secondLine", ','));
    }

    /**
     * @expectedException Ems\Core\Exceptions\DetectionFailedException
     **/
    public function test_header_throws_exception_if_following_line_count_doesnt_match()
    {
        $detector = $this->newDetector();
        $csv = $this->csvContent('simple-pipe-placeholder.csv');
        // Original file has 5 cols
        $csv .= "\n" . 'a,b,c,d,e,f';

        $detector->header($csv, ',');
    }

    /**
     * @expectedException Ems\Core\Exceptions\DetectionFailedException
     **/
    public function test_header_throws_exception_if_header_is_forced_and_file_contains_doubled_columns()
    {
        $detector = $this->newDetector()->setOption(CsvDetector::FORCE_HEADER_LINE, true);
        $csv = $this->csvContent('simple-pipe-placeholder.csv');
        $csv = str_replace(',street', ',name', $csv);
        $header = $detector->header($csv, ',');
    }

    /**
     * @expectedException Ems\Core\Exceptions\DetectionFailedException
     **/
    public function test_header_throws_exception_if_header_is_forced_and_file_contains_invalid_columns()
    {
        $detector = $this->newDetector()->setOption(CsvDetector::FORCE_HEADER_LINE, true);
        $csv = $this->csvContent('simple-pipe-placeholder.csv');
        $csv = str_replace(',street', ',17', $csv);
        $header = $detector->header($csv, ',');
    }

    public function test_header_returns_header_of_file_with_bom()
    {
        $detector = $this->newDetector();
        $csv = $this->csvContent('simple-pipe-placeholder-utf8-bom.csv');
        $header = ['id', 'name', 'last_name', 'age', 'street'];

        $detected = $detector->header($csv, ',');
        // Be very verbose here...
        foreach ($header as $i=>$key) {
            $this->assertTrue($key === $detected[$i], 'Header returns string with bom and shouldnt');
            $this->assertTrue(strlen($key) == strlen($detected[$i]));
        }
    }

    protected function csvContent($file, $separator=',')
    {
        $csvContent = isset($this->csvContents[$file]) ?
                      $this->csvContents[$file] :
                      static::dataFileContent($file);

        return str_replace(' | ', $separator, $csvContent);
    }

    protected function newDetector()
    {
        return new CsvDetector;
    }
}
