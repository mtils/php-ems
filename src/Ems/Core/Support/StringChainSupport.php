<?php
/**
 *  * Created by mtils on 24.11.17 at 05:57.
 **/

namespace Ems\Core\Support;


trait StringChainSupport
{
    /**
     * Parses a passed chain into a native format.
     *
     * @param string|array $chain
     *
     * @return array
     **/
    protected function parseChain($chain)
    {
        $parts = is_array($chain) ? $chain : explode('|', $chain);
        $parsed = [];

        foreach ($parts as $name) {
            list($operator, $key) = $this->splitExpression($name);

            $dotPos = strpos($key, ':');

            list($key, $parameters) = $dotPos ? explode(':', $key, 2) : [$key, ''];

            $parsed[$key] = [
                'operator'   => $operator,
                'parameters' => $parameters ? explode(',', $parameters) : [],
            ];
        }

        return $parsed;
    }

    /**
     * Cut the ! from the string.
     *
     * @param string $name
     *
     * @return array
     **/
    protected function splitExpression($name)
    {
        if (strpos($name, '!') === 0) {
            return ['-', ltrim($name, '!')];
        }

        return ['+', $name];
    }

}