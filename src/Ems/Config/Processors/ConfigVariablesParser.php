<?php
/**
 *  * Created by mtils on 2/20/21 at 5:47 PM.
 **/

namespace Ems\Config\Processors;


use RecursiveArrayIterator;

use function explode;
use function is_array;
use function is_scalar;
use function is_string;
use function str_replace;

/**
 * Class ConfigVariablesParser
 *
 * This class processes an array of (config) values. It replaces variables in it
 * by assigned values.
 * Basically the syntax is like this: {env.TIMEZONE}
 * This replaces this value by the TIMEZONE entry in the env.
 * This "env" is just an assigned array. A pool of variables.
 * So if you assign $parser->assign('server', $_SERVER) you can access all server
 * variables in config. (e.g {server.HTTP_HOST})
 *
 * You can also define default values by a pipe: {env.CACHE_DRIVER|file}
 * This will read CACHE_DRIVER in the assigned env array and if there is no hit
 * it returns the default "file".
 *
 * @package Ems\Config\Processors
 */
class ConfigVariablesParser
{
    /**
     * @var array
     */
    protected $assignments = [];

    /**
     * @var string
     */
    protected $regex = '/\{(?P<pool>[a-zA-Z0-9_]{1,16})\.(?P<key>[a-zA-Z0-9_\.]{1,64})[\|]{0,1}(?P<default>[^}]{0,128})\}/m';

    /**
     * @var string
     */
    protected $separator = '.';

    /**
     * Use this method to make this class a processor for config.
     *
     * @param array $config
     * @param array $originalConfig
     *
     * @return array
     */
    public function __invoke(array $config, array $originalConfig) : array
    {
        $this->assign('config', $config);
        $this->assign('originalConfig', $config);
        $copy = [];
        $iterator = new RecursiveArrayIterator($config);
        $this->parseNested($iterator, $copy);
        return $copy;
    }

    /**
     * Replace placeholders in $string.
     *
     * @param string $string
     * @return mixed
     */
    public function parse(string $string)
    {
        $matches = [];
        if (!preg_match_all($this->regex, $string, $matches)) {
            return $string;
        }

        $search = [];
        $replace = [];
        $matchCount = count($matches[0]);

        foreach ($matches[0] as $i=>$match) {

            $pool = $matches['pool'][$i];

            if (!isset($this->assignments[$pool])) {
                continue;
            }

            $variable = $matches['key'][$i];
            $default = $matches['default'][$i];

            $value = $this->getNestedValue(
                $this->assignments[$pool],
                $variable
            );

            if (!$value && $default) {
                $value = $default;
            }

            if ($matchCount === 1 && $string == $match) {
                return $value;
            }
            $search[] = $match;
            $replace[] = $value;

        }
        return $matchCount ? str_replace($search, $replace, $string) : $string;
    }

    /**
     * Assign some variables under $prefix. The can be accessed by {$prefix.key}
     *
     * @param string $prefix
     * @param array $variables
     */
    public function assign(string $prefix, array $variables)
    {
        $this->assignments[$prefix] = $variables;
    }

    /**
     * Return all assigned variables (under all prefixes).
     * @return array
     */
    public function getAssignments() : array
    {
        return $this->assignments;
    }

    /**
     * Parse all strings in an multidimensional array.
     *
     * @param RecursiveArrayIterator $iterator
     * @param array $copy
     */
    protected function parseNested(RecursiveArrayIterator $iterator, array &$copy)
    {
        while ($iterator->valid()) {
            if ($iterator->hasChildren()) {
                $child = [];
                $this->parseNested($iterator->getChildren(), $child);
                $copy[$iterator->key()] = $child;
                $iterator->next();
                continue;
            }
            $value = $iterator->current();
            $copy[$iterator->key()] = is_string($value) ? $this->parse($value) : $value;
            $iterator->next();
        }
    }

    protected function getNestedValue($root, $key)
    {
        $node = &$root;
        $segments = explode($this->separator, $key);
        $last = count($segments) - 1;

        for ($i = 0; $i <= $last; ++$i) {
            $node = @$this->getNode($node, $segments[$i]);

            if ($node === null) {
                return null;
            }

            if (!is_scalar($node) && $i != $last) {
                continue;
            }

            if ($i == $last) {
                return $node;
            }

            return null;
        }

        return null;
    }

    /**
     * Returns an array value reference or null if not found.
     *
     * @param mixed  $node (array|object)
     * @param string $key
     *
     * @return mixed
     **/
    protected function &getNode(&$node, string $key)
    {
        if (is_array($node) && isset($node[$key])) {
            return $node[$key];
        }
        $result = null;
        return $result;
    }
}