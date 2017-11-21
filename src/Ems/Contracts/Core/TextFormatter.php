<?php

namespace Ems\Contracts\Core;

/**
 * This is rather a view helper. Dont use the chain methods inside
 * methods that are called often. It makes text formatting much more readable
 * but has its costs.
 * Readable:.
 *
 * trim(escape(words($text, 30))) vs $t->format($text, trim|escape|words:30)
 **/
interface TextFormatter extends NamedCallableChain
{
    /**
     * Format the $text with the passed $filters
     * Filters can be an array of filter names or a pipe
     * separated string.
     *
     * @example $tf->format($text, 'trim|escape|words:30')
     * This would be resolved into:
     * [
     *    'trim',
     *    'escape',
     *    'words' => [30]
     * ]
     *
     * @param mixed        $text
     * @param array|string $filters
     *
     * @return string
     **/
    public function format($text, $filters = []);

    /**
     * Directly call a filter.
     *
     * @param string $filter
     * @param array  $params (optional)
     *
     * @return string
     **/
    public function __call($filter, array $params = []);
}
