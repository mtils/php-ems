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

use function print_r;
use function strpos;
use function trim;

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
    private $expandNestedKeys = true;

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
     * Should nested keys by supported? If yes you can give keys dots and they
     * will be converted to multidimensional arrays.
     *
     * @return bool
     */
    public function shouldExpandNestedKeys(): bool
    {
        return $this->expandNestedKeys;
    }

    /**
     * @see @self::shouldExpandNestedKeys()
     *
     * @param bool $expandNestedKeys
     * @return IniFileReader
     */
    public function setExpandNestedKeys(bool $expandNestedKeys): IniFileReader
    {
        $this->expandNestedKeys = $expandNestedKeys;
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
        $result = parse_ini_file($path, $this->shouldProcessSections(), $this->getReadMode());
        if ($result === false) {
            throw new RuntimeException("Ini file '$path' is not readable");
        }
        return $this->expandNestedGroups($result, true);
        if (!$this->shouldExpandNestedKeys()) {
            return $result;
        }
        return $this->expandNestedNames($result, $this->shouldProcessSections());
    }

    protected function expandNestedGroups(array $iniData, bool $processSections)
    {
        $separator = '.';

        if (!$processSections) {
            $iniData = [$iniData];
        }

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
        return $processSections ? $iniData : $iniData[0];
    }

    protected function expandNestedNames(array $iniData, bool $processSections)
    {
        $separator = '.';
        $escapeChar = "'";

        if (!$processSections) {
            $iniData = [$iniData];
        }

        foreach ($iniData as $sectionKey => $section) {
            // loop inside the section
            foreach ($section as $key => $value) {

                if (!strpos($key, $separator)) {
                    continue;
                }

                // The key was escaped, we just remove the escape chars
                if ($key[0] === $escapeChar) {
                    $new_key = trim($key, $escapeChar);
                    $iniData[$sectionKey][$new_key] = $value;
                    unset($iniData[$sectionKey][$key]);
                    continue;
                }

                // key has a escapeChar. Explode it, then parse each sub keys
                // and set value at the right place thanks to references
                $sub_keys = explode($separator, $key);
                $subs =& $iniData[$sectionKey];
                foreach ($sub_keys as $sub_key) {
                    if (!isset($subs[$sub_key])) {
                        $subs[$sub_key] = [];
                    }
                    $subs =& $subs[$sub_key];
                }
                // set the value at the right place
                $subs = $value;
                // unset the dotted key, we don't need it anymore
                unset($iniData[$sectionKey][$key]);

            }
        }
        return $processSections ? $iniData : $iniData[0];
    }
}