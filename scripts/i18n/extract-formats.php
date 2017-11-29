<?php

/*************************************************************************
 This script is used to extract the data from ldml directories.
 You can find them here: http://www.unicode.org/Public/cldr/
 The content needed for this script is typically in common/main
*************************************************************************/

define('DS', DIRECTORY_SEPARATOR);

include_once(__DIR__ . '/FormatExtractor.php');

function bye($message, $code=-1)
{
    echo "$message\n";
    exit($code);
}




if (count($argv) != 3) {
    $file = basename(__FILE__);
    bye("Usage: php $file \$inputdirectory \$outputdirectory");
}


$extractor = new FormatExtractor($argv[1], $argv[2]);

$extractor->convert();

echo "\n";
