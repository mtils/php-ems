<?php
/**
 *  * Created by mtils on 2/20/21 at 11:05 AM.
 **/

namespace Ems\Config\Readers;


use ArrayIterator;
use IteratorAggregate;
use RuntimeException;

use function explode;
use function parse_ini_file;
use function strpos;

use const INI_SCANNER_TYPED;

class IniFileReader implements IteratorAggregate
{
    /**
     * @var string
     */
    private $path = '';

    /**
     * @var int
     */
    private $readMode = INI_SCANNER_TYPED;

    /**
     * @var bool
     */
    private $processSections = true;

    /**
     * @var bool
     */
    private $expandNestedSections = true;

    public function __construct(string $path='', int $readMode=INI_SCANNER_TYPED, bool $processSections=true)
    {
        $this->setPath($path);
        $this->setReadMode($readMode);
        $this->setProcessSections($processSections);
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @param string $path
     * @return IniFileReader
     */
    public function setPath(string $path): IniFileReader
    {
        $this->path = $path;
        return $this;
    }

    /**
     * @return int
     */
    public function getReadMode(): int
    {
        return $this->readMode;
    }

    /**
     * @param int $readMode
     * @return IniFileReader
     */
    public function setReadMode(int $readMode): IniFileReader
    {
        $this->readMode = $readMode;
        return $this;
    }

    /**
     * @return bool
     */
    public function shouldProcessSections(): bool
    {
        return $this->processSections;
    }

    /**
     * @param bool $processSections
     * @return IniFileReader
     */
    public function setProcessSections(bool $processSections): IniFileReader
    {
        $this->processSections = $processSections;
        return $this;
    }

    /**
     * Should nested sections by supported? If yes you can give keys dots and they
     * will be converted to multidimensional arrays.
     *
     * @return bool
     */
    public function shouldExpandNestedSections(): bool
    {
        return $this->expandNestedSections;
    }

    /**
     * @param bool $expandNestedSections
     * @return IniFileReader
     * @see @self::shouldExpandNestedSections()
     *
     */
    public function setExpandNestedSections(bool $expandNestedSections): IniFileReader
    {
        $this->expandNestedSections = $expandNestedSections;
        return $this;
    }

    /**
     * @return ArrayIterator
     */
    public function getIterator()
    {
        $data = $this->path ? $this->parseIniFile($this->path) : [];
        return new ArrayIterator($data);
    }

    /**
     * Parse the ini file. Just reimplemented to ensure exceptions and guaranteed
     * array return type.
     *
     * @param string $path
     * @return array
     */
    protected function parseIniFile(string $path) : array
    {
        $result = @parse_ini_file($path, $this->shouldProcessSections(), $this->getReadMode());
        if ($result === false) {
            throw new RuntimeException("Ini file '$path' is not readable");
        }
        if (!$this->shouldProcessSections() || !$this->shouldExpandNestedSections()) {
            return $result;
        }
        return $this->expandNestedSections($result);
    }

    /**
     * @param array $iniData
     * @return array
     */
    protected function expandNestedSections(array $iniData)
    {
        $separator = '.';

        foreach ($iniData as $sectionKey => $section) {

            if (!strpos($sectionKey, $separator)) {
                continue;
            }
            $sub_keys = explode($separator, $sectionKey);
            $sectionData =& $iniData[$sectionKey];

            $node = &$iniData;
            foreach ($sub_keys as $sub_key) {
                if (!isset($node[$sub_key])) {
                    $node[$sub_key] = [];
                }
                $node =& $node[$sub_key];
            }
            $node = $sectionData;
            unset($iniData[$sectionKey]);
        }
        return $iniData;
    }

}