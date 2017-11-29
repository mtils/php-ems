<?php

require_once(__DIR__.'/DateFormatConverter.php');

class FormatExtractor
{

    /**
     * @var string
     **/
    protected $inputDir = '';

    /**
     * @var string
     **/
    protected $outputDir = '';

    /**
     * @var string
     **/
    protected $extension = 'xml';

    /**
     * @var array
     **/
    protected $verbosities = [
        'short'   => 'narrow',
        'long'    => 'abbreviated',
        'verbose' => 'wide'
    ];

    /**
     * @var array
     **/
    protected $widths = [];

    /**
     * @var array
     **/
    protected $dateVerbosities = [
        'short'     => 'short',
        'long'      => 'long',
        'verbose'   => 'full'
    ];

    /**
     * @var array
     **/
    protected $dateWidths = [];

    /**
     * @var array
     **/
    protected $weekDays = [
        'sun' => '7',
        'mon' => '1',
        'tue' => '2',
        'wed' => '3',
        'thu' => '4',
        'fri' => '5',
        'sat' => '6'
    ];

    /**
     * @var DateFormatConverter
     **/
    protected $formatConverter;

    public function __construct($inputDir, $outputDir)
    {
        $this->setInputDir($inputDir);
        $this->setOutputDir($outputDir);
        $this->widths = array_flip($this->verbosities);
        $this->dateWidths = array_flip($this->dateVerbosities);
        $this->formatConverter = new DateFormatConverter(include(__DIR__.'/ldml2phpdateformats.php'));
    }

    public function convert()
    {

        $inputFiles = $this->inputFiles();

        $mainLocales = $this->mainFiles($inputFiles);

//         print_r($mainLocales); die();
        foreach ($mainLocales as $locale) {
            /*if ($locale != 'de') {
                continue;
            }*/

                echo "\nMAIN Locale: $locale";

                $mainData = $this->readFile($locale);

                $this->writeFile($locale, $mainData);

                if (!$extensions = $this->extensions($locale, $inputFiles)) {
                    continue;
                }

                foreach ($extensions as $extension) {

                    echo "\nExtension: $extension";

                    if ($extensionData = $this->readFile($extension)) {
                        $this->writeFile($extension, $extensionData);
                    }
                    //$this->writeFile($extension, $this->mergeExtensionData($mainData, $extensionData));

                }

//              }
        }
    }

    public function getInputDir()
    {
        return $this->inputDir;
    }

    public function setInputDir($directory)
    {
        $this->inputDir = rtrim($directory, DIRECTORY_SEPARATOR);
        return $this;
    }

    public function getOutputDir()
    {
        return $this->outputDir;
    }

    public function setOutputDir($directory)
    {
        $this->outputDir = $directory;
        return $this;
    }

    protected function writeFile($locale, array $data)
    {
        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir);
        }

        $localeDir = $this->outputDir($locale);

        if (!is_dir($localeDir)) {
            mkdir($localeDir);
        }

        $path = $this->outputPath($localeDir, $locale);

//        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));

    }

    protected function mergeExtensionData(array $mainData, array $extensionData)
    {

        foreach (['decimal_separator', 'thousands_separator'] as $seperator) {
            if (isset($extensionData['number'][$seperator]) && $extensionData['number'][$seperator]) {
                $mainData['number'][$seperator] = $extensionData['number'][$seperator];
            }
        }

        foreach (['date', 'time', 'date_time'] as $unit) {
            foreach (['short', 'long', 'verbose'] as $verbosity) {
                if (isset($extensionData[$unit][$verbosity]) && $extensionData[$unit][$verbosity] ) {
                    $mainData[$unit][$verbosity] = $extensionData[$unit][$verbosity];
                }
            }
        }

        foreach (['week_days', 'months'] as $unit) {
            foreach (['short', 'long', 'verbose'] as $verbosity) {
                if (isset($extensionData[$unit][$verbosity]) && $extensionData[$unit][$verbosity] ) {
                    $mainData[$unit][$verbosity] = $extensionData[$unit][$verbosity];
                }
            }
        }

        if (isset($extensionData['money']['format']) && $extensionData['money']['format']) {
            $mainData['money']['format'] = $extensionData['money']['format'];
        }

        return $mainData;

    }

    protected function inputFiles()
    {
        if (!$handle = opendir($this->getInputDir())) {
            throw new Exception('Could not open the input directory');
        }

        $files = [];

        while (false !== ($entry = readdir($handle))) {
            if (!$entry || $entry[0] == '.') {
                continue;
            }
            $files[] = pathinfo($entry, PATHINFO_FILENAME);
        }

        sort($files);

        // Make index nice after sort
        return array_values($files);
    }

    protected function mainFiles($inputFiles)
    {
        return array_values(array_filter($inputFiles, function ($file) {
            return strpos($file, '_') === false;
        }));
    }

    protected function extensions($mainFile, $inputFiles)
    {

        $prefix = $mainFile . '_';

        return array_filter($inputFiles, function ($file) use ($prefix) {
            return strpos($file, $prefix) === 0;
        });

    }

    protected function readFile($lang)
    {

        $xml = simplexml_load_file($this->path($lang));

        $data = [];

        if ($numberSigns = $this->numberSigns($xml)) {
            $data['number'] = $numberSigns;
        }

        if ($unitFormat = $this->baseUnitFormat($xml)){
            $data['unit'] = $unitFormat;
        }

        if ($moneyFormat = $this->moneyFormat($xml)) {
            $data['money'] = $moneyFormat;
        }

        if ($dateFormats = $this->dateFormats($xml)) {
            $data['date'] = $dateFormats;
        }

        if ($timeFormats = $this->timeFormats($xml)) {
            $data['time'] = $timeFormats;
        }

        if ($dateTimeFormats = $this->dateTimeFormats($xml, $dateFormats, $timeFormats)) {
            $data['datetime'] = $dateTimeFormats;
        }

        if ($weekDayNames = $this->weekDayNames($xml)) {
            $data['weekday'] = $weekDayNames;
        }

        if ($monthNames = $this->monthNames($xml)) {
            $data['month'] = $monthNames;
        }

        return $data;

    }

    protected function monthNames(SimpleXmlElement $xml)
    {

        $months = [];

        if (!$xmlData = $this->calendarData($xml)) {
            return $months;
        }

        if (!$xmlData->months) {
            return $months;
        }

        foreach ($xmlData->months->children() as $monthContext) {
            foreach ($monthContext->children() as $monthWidth) {

                if (!isset($monthWidth['type'])) {
                    continue;
                }

                $width = (string)$monthWidth['type'];

                if (!isset($this->widths[$width])) {
                    continue;
                }

                $verbosity = $this->widths[$width];

                $months[$verbosity] = [];

                foreach ($monthWidth->children() as $month) {
                    $months[$verbosity][(string)$month['type']] = (string)$month;
                }

            }

        }

        return $months;
    }

    protected function weekDayNames(SimpleXmlElement $xml)
    {

        $months = [];

        if (!$xmlData = $this->calendarData($xml)) {
            return $months;
        }

        if (!$xmlData->days) {
            return [];
        }

        foreach ($xmlData->days->children() as $dayContext) {
            foreach ($dayContext->children() as $dayWidth) {

                if (!isset($dayWidth['type'])) {
                    continue;
                }

                $width = (string)$dayWidth['type'];

                if (!isset($this->widths[$width])) {
                    continue;
                }

                $verbosity = $this->widths[$width];

                $months[$verbosity] = [];

                foreach ($dayWidth->children() as $day) {

                    $dayType = (string)$day['type'];

                    if (!isset($this->weekDays[$dayType])) {
                        continue;
                    }

                    $dayNumber = $this->weekDays[$dayType];

                    $months[$verbosity][$dayNumber] = (string)$day;
                }

            }

        }

        return $months;
    }

    protected function dateFormats(SimpleXmlElement $xml)
    {

        $formats = [];

        if (!$calendarData = $this->calendarData($xml)) {
            return [];
        }

        if (!$calendarData->dateFormats) {
            return [];
        }

        foreach ($calendarData->dateFormats->children() as $child) {

            if ($child->getName() != 'dateFormatLength') {
                continue;
            }

            $dateFormatLength = $child;

            if (!isset($dateFormatLength['type'])) {
                continue;
            }

            $width = (string)$dateFormatLength['type'];

            if (!isset($this->dateWidths[$width])) {
                continue;
            }

            $verbosity = $this->dateWidths[$width];

            $pattern = (string)$dateFormatLength->dateFormat->pattern;

//             $dateTime = new DateTime();
            
            $converted = $this->formatConverter->convert($pattern);
//             echo "\n$pattern -> $converted -> " . $dateTime->format($converted);
            
            $formats[$verbosity] = $converted;
//             echo "\n$verbosity: $pattern " . $dateTime->format($pattern);


        }

        return $formats;
    }

    protected function timeFormats(SimpleXmlElement $xml)
    {

        $formats = [];

        if (!$calendarData = $this->calendarData($xml)) {
            return [];
        }

        if (!$calendarData->timeFormats) {
            return [];
        }

        foreach ($calendarData->timeFormats->children() as $child) {

            if ($child->getName() != 'timeFormatLength') {
                continue;
            }

            $timeFormatLength = $child;

            if (!isset($timeFormatLength['type'])) {
                continue;
            }

            $width = (string)$timeFormatLength['type'];

            if (!isset($this->dateWidths[$width])) {
                continue;
            }

            $verbosity = $this->dateWidths[$width];

            $pattern = (string)$timeFormatLength->timeFormat->pattern;

//             $dateTime = new DateTime();
            
            $converted = $this->formatConverter->convert($pattern);
//             echo "\n$pattern -> $converted -> " . $dateTime->format($converted);
            
            $formats[$verbosity] = $converted;
//             echo "\n$verbosity: $pattern " . $dateTime->format($pattern);


        }

        return $formats;
    }

    protected function dateTimeFormats(SimpleXmlElement $xml, $dateFormats, $timeFormats)
    {

        $formats = [];

        if (!$calendarData = $this->calendarData($xml)) {
            return [];
        }

        if (!$calendarData->dateTimeFormats) {
            return [];
        }

        foreach ($calendarData->dateTimeFormats->children() as $child) {

            if ($child->getName() != 'dateTimeFormatLength') {
                continue;
            }

            $dateTimeFormatLength = $child;

            if (!isset($dateTimeFormatLength['type'])) {
                continue;
            }

            $width = (string)$dateTimeFormatLength['type'];

            if (!isset($this->dateWidths[$width])) {
                continue;
            }

            $verbosity = $this->dateWidths[$width];

            $pattern = (string)$dateTimeFormatLength->dateTimeFormat->pattern;

            if (!isset($dateFormats[$verbosity]) || !isset($timeFormats[$verbosity])) {
                continue;
            }

//             $dateTime = new DateTime();
            
            $converted = $this->formatConverter->convertDateTimeFormat(
                            $pattern,
                            $dateFormats[$verbosity],
                            $timeFormats[$verbosity]
                         );

            //echo "\n$pattern -> $converted -> " . $dateTime->format($converted);
            
            $formats[$verbosity] = $converted;
//             echo "\n$verbosity: $pattern " . $dateTime->format($pattern);


        }

        return $formats;
    }

    protected function calendarData(SimpleXmlElement $xml, $type='gregorian')
    {
        if (!$xml->dates || !$xml->dates->calendars) {
            return;
        }

        foreach ($xml->dates->calendars->children() as $calendar) {
            if ($calendar['type'] == $type) {
                return $calendar;
            }
        }
    }

    protected function numberSigns(SimpleXmlElement $xml, $type='latn')
    {

        if (!$signs = $this->numberData($xml, 'symbols', $type)) {
            return [];
        }

        $decimal = (string)$signs->decimal;

        $formats = [];

        if ($this->hasChild($signs, 'decimal')) {
            $formats['decimal_mark'] = (string)$signs->decimal;
        }

        if ($this->hasChild($signs, 'group')) {
            $formats['thousands_separator'] = (string)$signs->group;
        }

        return $formats;
        return [
            'decimal_mark' => $decimal ? $decimal : '.',
            'thousands_separator' => (string)$signs->group
        ];

    }

    protected function hasChild(SimpleXMLElement $node, $childTagName)
    {
        foreach ($node->children() as $child) {
            if ($child->getName() == $childTagName) {
                return true;
            }
        }

        return false;
    }


    protected function baseUnitFormat(SimpleXMLElement $xml)
    {
        if (!$format = $this->unitFormat($xml, 'length-meter')) {
            return '';
        }

        if (strpos($format, '{0} ') === 0) {
            return '{number} {unit}';
        }

        if (strpos($format, '{0}') === 0) {
            return '{number}{unit}';
        }

        if (strpos($format, ' {0}')) {
            return '{unit} {number}';
        }

        if (strpos($format, '{0}')) {
            return '{unit}{number}';
        }

        return '{number} {unit}';

    }

    protected function unitFormat(SimpleXmlElement $xml, $unit, $count="other", $type='latn')
    {

        if (!$unit = $this->unitData($xml, $unit)) {
            return '';
        }

        foreach ($unit->children() as $child) {
            if ($child['count'] == $count) {
                return (string)$child;
            }
        }

        return '';

    }

    protected function moneyFormat(SimpleXmlElement $xml, $type='latn')
    {

        if (!$formats = $this->numberData($xml, 'currencyFormats', $type)) {
            return '';
        }

        foreach ($formats->children() as $child) {
            if ( $child->getName() != 'unitPattern' || $child['count'] != 'other') {
                continue;
            }

            $pattern = (string)$child;

            return str_replace(['{0}', '{1}'], ['{number}', '{currency}'], $pattern);

        }

        return '';

    }

    /**
     * @param SimpleXmlElement $xml
     * @param $node
     * @param string $type
     *
     * @return SimpleXMLElement
     */
    protected function numberData(SimpleXmlElement $xml, $node,  $type='latn')
    {

        if (!$xml->numbers) {
            return;
        }

        foreach ($xml->numbers->children() as $child) {
            if ( $child->getName() == $node && $child['numberSystem'] == $type) {
                return $child;
            }
            
        }
    }

    protected function unitData(SimpleXmlElement $xml, $unit, $type='narrow')
    {

        if (!$xml->units) {
            return;
        }
        foreach ($xml->units->children() as $child) {

            foreach ($child->children() as $unitElement) {
                if ($unitElement['type'] == $unit) {
                    return $unitElement;
                }
            }

        }
    }

    protected function path($lang)
    {
        return $this->inputDir . DS . $lang . '.' . $this->extension;
    }

    protected function outputPath($dir, $lang)
    {
        return $dir . DS . 'formats.json';
    }

    protected function outputDir($lang)
    {
        return $this->outputDir . DS . $lang;
    }

}
