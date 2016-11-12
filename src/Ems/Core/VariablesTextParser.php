<?php

namespace Ems\Core;

use Ems\Contracts\Core\TextParser;

class VariablesTextParser implements TextParser
{
    protected $preDelimiter = '{';

    protected $postDelimiter = '}';

    protected $nestingSeparator = '.';

    /**
     * {@inheritdoc}
     *
     * @param string $text
     * @param array  $data              The view variables
     * @param bool   $purgePlaceholders (optional)
     *
     * @return string
     **/
    public function parse($text, array $data, $purgePlaceholders = true)
    {
        if (!$matches = $this->getMatches($text)) {
            return $text;
        }

        $parsed = $this->replaceWithData($text, $matches, $data);

        return $purgePlaceholders ? $this->purge($parsed) : $parsed;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $text
     *
     * @return string The purged text
     **/
    public function purge($text)
    {
        if (!$matches = $this->getMatches($text)) {
            return $text;
        }

        return str_replace($matches, '', $text);
    }

    /**
     * Returns all matches of variables inside $text.
     *
     * @param string
     *
     * @return array
     **/
    protected function getMatches($text)
    {
        $matches = [];
        if (!$found = preg_match_all($this->buildRegex(), $text, $matches)) {
            return [];
        }

        return $matches[0];
    }

    /**
     * Replaces the matches $matches with data from $data inside $text.
     *
     * @param string $text
     * @param array  $matches
     * @param array  $data
     *
     * @return string
     **/
    protected function replaceWithData($text, array $matches, array $data)
    {
        list($search, $replace) = $this->getSearchAndReplace($matches, $data);

        return str_replace($search, $replace, $text);
    }

    /**
     * Get two arrays suitable for str_replace to replace all variables.
     *
     * @param array $matches
     * @param array $data
     *
     * @return array
     **/
    protected function getSearchAndReplace(array $matches, array $data)
    {
        $count = count($matches);
        $alreadySetted = [];
        $search = [];
        $replace = [];

        for ($i = 0, $m = 0; $i < $count; ++$i) {
            if (isset($alreadySetted[$matches[$i]])) {
                continue;
            }

            $value = $this->extractValue($matches[$i], $data);

            if ($value === null) {
                continue;
            }

            $search[$m] = $matches[$i];
            $replace[$m] = $value;
            $alreadySetted[$matches[$i]] = true;
            ++$m;
        }

        return [$search, $replace];
    }

    /**
     * Get the value from $data.
     *
     * @param string $key
     * @param array  $data
     *
     * @return mixed (scalar|null)
     **/
    protected function extractValue($key, $data)
    {
        $real_varname = mb_substr($key, 1, mb_strlen($key) - 2);

        $key = $this->cleanKey($key);

        if (isset($data[$key])) {
            return $data[$key];
        }

        if (!$this->isNestedKey($key)) {
            return;
        }

        $segments = explode($this->nestingSeparator, $key);

        $last = count($segments) - 1;
        $varname = $segments[0];

        if (!isset($data[$varname])) {
            return;
        }

        $node = &$data[$varname];

        //$i=1 because view varname is still inside segments
        for ($i = 1; $i <= $last; ++$i) {
            $node = @$this->getNode($node, $segments[$i]);

            if ($node === null) {
                return;
            }

            if (is_scalar($node)) {
                if ($i == $last) {
                    return $node;
                }

                return;
            }
        }
    }

    /**
     * Returns an array key if node is an array else a property.
     *
     * @param mixed  $node (array|object)
     * @param string $key
     *
     * @return mixed
     **/
    protected function &getNode(&$node, $key)
    {
        if (is_object($node) && isset($node->$key)) {
            return $node->$key;
        }

        if (is_array($node) && isset($node[$key])) {
            return $node[$key];
        }

        // Special handling of eloquent relations
        if (!$this->isEloquentModel($node)) {
            $result = null;

            return $result;
        }

        if (!method_exists($node, $key)) {
            $result = null;

            return $result;
        }

        $relation = $node->{$key}();

        if (!$this->isEloquentRelation($relation)) {
            $result = null;

            return $result;
        }

        return $relation->getResults();
    }

    /**
     * Cleans the delimiters out of key.
     *
     * @param string $key
     *
     * @return string
     **/
    protected function cleanKey($key)
    {
        return mb_substr(
            $key,
            mb_strlen($this->preDelimiter),
            mb_strlen($key) - mb_strlen($this->postDelimiter) - 1
        );
    }

    /**
     * @param string $key
     *
     * @return bool
     **/
    protected function isNestedKey($key)
    {
        return (int) mb_strpos($key, $this->nestingSeparator) > 0;
    }

    /**
     * @return string
     **/
    protected function buildRegex()
    {
        return '/\\'.$this->preDelimiter.
               '[a-zA-Z0-9\\'.$this->nestingSeparator.
               '_]+\\'.$this->postDelimiter.'/';
    }

    /**
     * Return true if $node is an eloquent model (without needing 
     * the actual classes).
     *
     * @param mixed $node
     *
     * @return bool
     **/
    protected function isEloquentModel($node)
    {
        return is_object($node) && get_class($node) == 'Illuminate\Database\Eloquent\Model';
    }

    /**
     * Return true if $node is an eloquent relation (without needing 
     * the actual classes).
     *
     * @param mixed $relation
     *
     * @return bool
     **/
    protected function isEloquentRelation($relation)
    {
        return is_object($node) && get_class($node) == 'Illuminate\Database\Eloquent\Relations\Relation';
    }
}
