<?php

namespace Ems\Core\Filesystem;

use Ems\Contracts\Core\Configurable;
use Ems\Core\Exceptions\DetectionFailedException;
use Ems\Core\ConfigurableTrait;


/**
 * The CSV Detector tries to guess the csv format. Even if the most people
 * will say that this cannot be done exactly, I would say exactly enough
 * to justify an implementation
 **/
class CsvDetector implements Configurable
{

    use ConfigurableTrait;

    /**
     * @var string
     **/
    const FORCE_HEADER_LINE = 'force_header_LINE';

    /**
     * @var array
     **/
    protected $defaultOptions = [
        self::FORCE_HEADER_LINE => false
    ];

    /**
     * @var array
     **/
    protected $separators = [
        ',', ';', "\t", '|', '^'
    ];

    /**
     * Detect the separator char. CSV Files with one line are invalid by
     * definition.
     *
     * @param string $firstLines
     * @param string $delimiter (optional)
     *
     * @return string
     **/
    public function separator($firstLines, $delimiter='"')
    {
        $lines = $this->toCheckableLines($firstLines);

        $separatorsByCount = $this->sortSeparatorsByHighestCount($lines[0], $delimiter);
        $highestCount = max(array_keys($separatorsByCount));

        // If the highest count of colums is smaller than 2 there should be
        // no separator at all. If the next lines are seem to have more than one
        // detection should fail.
        if ($highestCount < 2) {
            return '';
        }

        foreach ($separatorsByCount as $count=>$separators) {
            if ($count < 2) {
                continue;
            }
            foreach ($separators as $separator) {
                if (!$this->eachLineHasColumnCountOf($lines, $count, $separator, $delimiter)) {
                    continue;
                }
                return $separator;
            }
        }

        // if we didnt find an applicable separator until here we have to fail
        throw new DetectionFailedException("The csv file seems to have $highestCount columns but the following lines contains another count of columns");
    }

    /**
     * Return the header. The header is an indexed array of column names
     * (['id', 'name', 'first_name', 'age'])
     * If the header names cannot be detected it will return an indexed array
     * with the amount of columns.
     * So in this implementation you can skip the first row in imports if
     * $headers[0] exists.
     *
     * @param string $firstLines
     * @param string $separator
     * @param string $delimiter (optional)
     *
     * @return array
     **/
    public function header($firstLines, $separator, $delimiter='"')
    {

        $lines = $this->toCheckableLines($firstLines);
        $row = str_getcsv($lines[0], $separator, $delimiter);

        $header = $this->guessHeader($row);
        $columnCount = count($header);

        if (!$this->eachLineHasColumnCountOf($lines, $columnCount, $separator, $delimiter)) {
            throw new DetectionFailedException("Header seems to have $columnCount columns but following rows didnt match");
        }


        return $header;
    }

    /**
     * Return the list of seperators which should be detected.
     *
     * @return array
     **/
    public function getSeparators()
    {
        return $this->separators;
    }

    /**
     * Add a separator which should be detected.
     *
     * @param string $separator
     *
     * @return self
     **/
    public function addSeparator($separator)
    {
        $this->separators[] = $separator;
        return $this;
    }

    /**
     * Return an array keyed by the column count of each separator
     *
     * @param string $line
     * @param string $delimiter
     *
     * @return array
     **/
    protected function sortSeparatorsByHighestCount($line, $delimiter)
    {
        $separatorsByCount = [];

        foreach ($this->separators as $separator) {
            $data = str_getcsv($line, $separator, $delimiter);
            $count = count($data);

            if (!isset($separatorsByCount[$count])) {
                $separatorsByCount[$count] = [];
            }

            $separatorsByCount[$count][] = $separator;
        }

        $sorted = [];
        $counts = array_keys($separatorsByCount);

        rsort($counts, SORT_NUMERIC);

        foreach ($counts as $count) {
            $sorted[$count] = $separatorsByCount[$count];
        }

        return $sorted;
    }

    /**
     * Check if every line has an equal amount of columns (assumed the separator
     * is not wrong)
     *
     * @param array  $lines
     * @param int    $count
     * @param string $separator
     * @param string $delimiter
     *
     * @return bool
     **/
    protected function eachLineHasColumnCountOf(array $lines, $count, $separator, $delimiter)
    {
        foreach ($lines as $line) {
            $row = str_getcsv($line, $separator, $delimiter);

            if (static::isSkippableRow($row)) {
                continue;
            }

            if ($count != count($row)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Read each line into an array. If there is only one or zero lines fail.
     *
     * @param string $firstLines
     *
     * @return array
     **/
    protected function toCheckableLines($firstLines)
    {

        $lines = explode("\n", $firstLines);

        if (count($lines) < 2) {
            throw new DetectionFailedException('No lines found to detect separator');
        }

        return $lines;
    }

    /**
     * Check if every passed columnName in the passed array is actually one
     *
     * @param array $row
     *
     * @return bool
     **/
    protected function containsOnlyColumnNames(array $row)
    {
        $last = count($row)-1;

        foreach ($row as $i=>$column) {
            if (!$this->isColumnName($column, $i == $last)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check if a supposed column name is really one
     *
     * @param string $column
     * @param bool   $allowEmpty (default:false)
     *
     * @return bool
     **/
    protected function isColumnName($column, $allowEmpty=false)
    {
        if (is_numeric($column)) {
            return false;
        }

        if (trim($column) === '') {
            return $allowEmpty ? true : false;
        }

        return preg_match('/\w*[a-zA-Z]\w*/u', $column) > 0;
    }

    /**
     * Check if all column names are unique
     *
     * @param array $row
     *
     * @return bool
     **/
    protected function columnsAreUnique(array $row)
    {
        return count(array_flip($row)) == count($row);
    }

    /**
     * @param array $row
     *
     * @return array
     **/
    protected function guessHeader(array $row)
    {
        $containsOnlyColumnNames = $this->containsOnlyColumnNames($row);
        $columnsAreUnique = $this->columnsAreUnique($row);

        if ($containsOnlyColumnNames && $columnsAreUnique) {
            return $row;
        }

        if (!$this->getOption(self::FORCE_HEADER_LINE)) {
            return range(0, count($row)-1);
        }

        if (!$containsOnlyColumnNames) {
            $hint = implode(', ', $this->collectInvalidColumns($row));
            throw new DetectionFailedException("No header found because the following columns do not like a column name: [$hint]");
        }

        // !$columnsAreUnique)
        $hint = implode(', ', $this->collectDoubledColumns($row));
        throw new DetectionFailedException("No header found because the following columns occurs more than once: [$hint]");

    }

    /**
     * Collects invalid column names for better error messages
     *
     * @param array $row
     *
     * @return array
     **/
    protected function collectInvalidColumns(array $row)
    {
        $invalidColumns = [];

        foreach ($row as $column) {
            if (!$this->isColumnName($column)) {
                $invalidColumns[] = $column;
            }
        }
        return $invalidColumns;
    }

    /**
     * Collects double column names for better error messages
     *
     * @param array $row
     *
     * @return array
     **/
    protected function collectDoubledColumns(array $row)
    {
        $foundNames = [];
        $doubledColumns = [];

        foreach ($row as $column) {

            if (isset($foundNames[$column])) {
                $doubledColumns[] = $column;
            }
            $foundNames[$column] = true;
        }

        return $doubledColumns;
    }

    /**
     * Finds out if a row is skippable. This is the case if it is an empty row
     * returned by php functions ([0] => null) or empty Excel export rows.
     *
     * @param array $row
     *
     * @return bool
     **/
    public static function isSkippableRow(array $row)
    {
        if (array_key_exists(0, $row) && $row[0] === null) {
            return true;
        }
        return false;
    }
}
