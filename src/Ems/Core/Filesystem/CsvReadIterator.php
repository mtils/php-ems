<?php

namespace Ems\Core\Filesystem;

use Ems\Contracts\Core\Filesystem;
use Ems\Contracts\Core\ContentIterator;
use Ems\Contracts\Core\Configurable;
use Ems\Contracts\Core\StringConverter;
use Ems\Core\StringConverter\MBStringConverter;
use Ems\Core\ConfigurableTrait;
use Ems\Core\LocalFilesystem;
use Ems\Core\Helper;


/**
 * The CsvReadIterator is an iterator which allows to read
 * csv files row by row.
 * If you do not set headers or set any separators/delimiters
 * it tries to guess it via the CsvDetector.
 *
 * @sample foreach (new CsvReadIterator($file) as $row) ...
 *
 * Definitions used in this class:
 *
 * row: One row of data. The header is excluded, so row[0] is never the header
 * header: an indexed array of column names.
 * separator: The column separator (,;|)
 * delimiter: The string delimiter if strings contain the separator or \n
 **/
class CsvReadIterator implements ContentIterator
{
    use ReadIteratorTrait;
    use ConfigurableTrait;

    /**
     * @var string
     **/
    const ENCODING = 'encoding';

    /**
     * @var string
     **/
    protected $separator = '';

    /**
     * @var string
     **/
    protected $delimiter = '"';

    /**
     * @var array
     **/
    protected $header = [];

    /**
     * Caching of header presence
     *
     * @var bool
     **/
    protected $hasHeader = false;

    /**
     * @var bool
     **/
    private $headerRowSkipped = false;

    /**
     * @var bool
     **/
    private $separatorWasDetected = false;

    /**
     * @var bool
     **/
    private $headerWasDetected = false;

    /**
     * @var int
     **/
    protected $startAtRow;

    /**
     * @var CsvDetector
     **/
    protected $detector;

    /**
     * @var LineReadIterator
     **/
    protected $lineReader;

    /**
     * @var string
     **/
    protected $firstLines;

    /**
     * @var self
     **/
    protected $countInstance;

    /**
     * @var array
     **/
    protected $defaultOptions = [
        self::ENCODING => 'utf-8'
    ];

    /**
     * @var StringConverter
     **/
    protected $stringConverter;

    /**
     * @var bool
     **/
    protected $shouldConvert = false;

    /**
     * @param string     $filePath   (optional)
     * @param Filesystem $filesystem (optional)
     **/
    public function __construct($filePath = '', Filesystem $filesystem = null, CsvDetector $detector = null, LineReadIterator $lineReader = null)
    {
        $this->position = 0;
        $this->filePath = $filePath;
        $this->chunkSize = 0;
        $this->setFilesystem($filesystem ?: new LocalFilesystem());
        $this->setDetector($detector ?: new CsvDetector());
        $this->setLineReader($lineReader ?: new LineReadIterator());
        $this->stringConverter = new MBStringConverter;
    }

    /**
     * Return the column separator
     *
     * @return string
     **/
    public function getSeparator()
    {
        if (!$this->separator && $this->getFilePath()) {
            $this->separator = $this->detector->separator(
                $this->firstLines(),
                $this->getDelimiter()
            );
            $this->separatorWasDetected = true;
        }

        return $this->separator;
    }

    /**
     * Set the column separator sign
     *
     * @param string $separator
     *
     * @return self
     **/
    public function setSeparator($separator)
    {
        $this->separator = $separator;
        return $this;
    }

    /**
     * Get the string delimiter (for strings containing newlines or separators)
     *
     * @return string
     **/
    public function getDelimiter()
    {
        return $this->delimiter;
    }

    /**
     * Set the string delimiter (for strings containing newlines or separators)
     *
     * @param string $delimiter
     *
     * @return self
     **/
    public function setDelimiter($delimiter)
    {
        $this->delimiter = $delimiter;
        return $this;
    }

    /**
     * @return CsvDetector
     **/
    public function getDetector()
    {
        return $this->detector;
    }

    /**
     * @param CsvDetector $detector
     *
     * @return self
     **/
    public function setDetector(CsvDetector $detector)
    {
        $this->detector = $detector;
        return $this;
    }

    /**
     * @return LineReadIterator
     **/
    public function getLineReader()
    {
        return $this->lineReader;
    }

    /**
     * @param LineReadIterator $lineReader
     *
     * @return self
     **/
    public function setLineReader(LineReadIterator $lineReader)
    {
        $this->lineReader = $lineReader;
        if ($this->filesystem) {
            $this->lineReader->setFilesystem($this->filesystem);
        }
        if ($this->filePath) {
            $this->lineReader->setFilePath($this->filePath);
        }
        return $this;
    }

    public function getStringConverter()
    {
        return $this->stringConverter;
    }

    /**
     * Return the csv header. A header is an array of column names ['id', 'name'...].
     *
     * @return array
     **/
    public function getHeader()
    {
        if ($this->hasHeader) {
            return $this->header;
        }

        if (!$this->getFilePath()) {
            return $this->header;
        }

        $header = $this->detector->header(
            $this->firstLines(),
            $this->getSeparator(),
            $this->getDelimiter()
        );

        $this->headerWasDetected = !$this->isNumericHeader($header);

        $this->setHeader($this->headerWasDetected ? $header : []);

        return $this->header;
    }

    /**
     * Set the csv file header. An header is a indexed array of column
     * names ['id', 'name'].
     * Set no header or an empty array to let CsvDetector detect the
     * header.
     * If an header was set (count($this->header) the first line
     * will automatically skipped
     *
     * @param array $header
     *
     * @return self
     **/
    public function setHeader(array $header)
    {
        $this->header = $header;
        $this->hasHeader = (bool)count($header);
        return $this;
    }

    /**
     * Return the row count of the file
     *
     * @return int
     **/
    public function count()
    {
        foreach ($this->newCountInstance() as $i=>$row) {
        }
        return $i+1;
    }

    /**
     * Read the next row and return it. Skip empty lines.
     *
     * @param resource $handle
     * @param int      $chunkSize
     *
     * @return array|null
     **/
    protected function readNext($handle, $chunkSize)
    {
        if (feof($handle)) {
            return;
        }

        $row = $this->readRow($handle, $chunkSize);

        if (!$this->startAtRow && $this->hasHeader && !$this->headerRowSkipped) {
            $this->headerRowSkipped = true;
            return $this->readNext($handle, $chunkSize);
        }

        return $row === [] ? $this->readNext($handle, $chunkSize) : $row;
    }

    /**
     * Read the next row from the file
     *
     * @param resource $handle
     * @param int      $chunkSize (ignored)
     *
     * @return array
     **/
    protected function readRow($handle, $chunkSize)
    {
        $row = str_getcsv($this->readLine($handle, 0), $this->separator, $this->delimiter);

        if ($this->isSkippableRow($row)) {
            return [];
        }

        if (!$this->hasHeader) {
            return $this->convertEncoding($row);
        }

        $namedRow = [];

        foreach ($this->header as $i=>$column) {
            $namedRow[$column] = $this->convertEncoding($row[$i]);
        }

        return $namedRow;
    }

    /**
     * A skippable row is a (by php) empty row or a row containing no data. This
     * happens often with spreadsheet programs like excel. Here you often have
     * ;;;;;;;;;;;; lines because excel cant determine the end of the file
     * correctly.
     *
     * @param array $row
     *
     * @return bool
     **/
    protected function isSkippableRow(array $row)
    {
        // PHP handles empty LINES (just a \n) with a special array...i love php
        if ( (count($row) == 1) && ($row[0] == null) ) {
            return true;
        }

        // Test if any of the values is not trimmed ""
        foreach ($row as $value) {
            if (trim($value) !== '') {
                return false;
            }
        }
        return true;
    }

    /**
     * Empty first line cache if file did change
     **/
    protected function onFileChanged()
    {
        $this->firstLines = null;
        if ($this->separatorWasDetected) {
            $this->separator = '';
            $this->separatorWasDetected = false;
        }
        if ($this->headerWasDetected) {
            $this->setHeader([]);
            $this->headerWasDetected = false;
        }
    }

    /**
     * Empty first line cache if file did change
     **/
    protected function onRewind()
    {
        $this->headerRowSkipped = false;
        $this->shouldConvert = strtolower($this->getOption(self::ENCODING)) != 'utf-8';

        // Trigger detection once
        $this->getHeader();
    }

    /**
     * Return the first lines of the input file
     *
     * @param int $lineCount
     *
     * @return string
     **/
    protected function firstLines($lineCount=20)
    {
        if ($this->firstLines !== null) {
            return $this->firstLines;
        }

        $this->lineReader->setFilePath($this->filePath);

        $lines = [];

        foreach ($this->lineReader as $i=>$line) {
            $lines[] = $line;

            if ($i >= $lineCount) {
                break;
            }
        }

        $this->lineReader->releaseHandle();

        $this->firstLines = $this->convertEncoding(implode("\n", $lines));

        return $this->firstLines;

    }

    /**
     * Returns if the passed header is a numeric default header of CsvDetector
     * if it can detect the amount of columns but no names
     *
     * @param array $header
     *
     * @return bool
     **/
    protected function isNumericHeader($header)
    {
        return $header == range(0, count($header)-1);
    }

    /**
     * Create an instance of this reader just for the line count
     *
     * @return self
     **/
    protected function newCountInstance()
    {
        $instance = new static(
            $this->getFilePath(),
            $this->getFilesystem(),
            $this->getDetector(),
            clone $this->getLineReader()
        );
        return $instance->setDelimiter($this->getDelimiter())
                        ->setSeparator($this->getSeparator())
                        ->setHeader($this->getHeader());
    }

    /**
     * Converts encoding if needed.
     *
     * @param array|string $data
     *
     * @return array|string
     **/
    protected function convertEncoding($data)
    {

        if (!$this->shouldConvert) {
            return $data;
        }

        if (!is_array($data)) {
            return $this->stringConverter->convert("$data", 'utf-8', $this->getOption(self::ENCODING));
        }
dd('Issoch array');
        $converted = [];

        foreach ($data as $key=>$value) {
            $converted[$key] = $this->convert($data);
        }

        return $converted;
    }
}
