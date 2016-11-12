<?php

namespace Ems\Contracts\Core;

interface TextParser
{
    /**
     * Parses string text and replaces all occurences of placeholders. The parser
     * should not remove any unknown placeholders if $purgePlaceholders is false.
     * Other parsers could handle them after parsing with this parser.
     * Remove all unknown placeholders in purge or instantly if $purgePlaceholders is true.
     *
     * @param string $text
     * @param array  $data              The view variables
     * @param bool   $purgePlaceholders (optional)
     *
     * @return string
     **/
    public function parse($text, array $data, $purgePlaceholders = true);

    /**
     * Clean all unknown placeholders from text (replace with '').
     *
     * @param string $text
     *
     * @return string The purged text
     **/
    public function purge($text);
}
