<?php
/**
 *  * Created by mtils on 06.07.19 at 19:58.
 **/

namespace Ems\Routing;


use function array_shift;
use function preg_replace;
use function preg_replace_callback;
use function strlen;
use function substr;
use function trim;

class CurlyBraceRouteCompiler
{

    /**
     * Replace named and wildcard parameters. If you just use on of both methods
     * consider to not call compile.
     *
     * @param string $pattern
     * @param array $parameters
     *
     * @return string
     */
    public function compile(string $pattern, array &$parameters) : string
    {
        $pattern = $this->replaceNamed($pattern, $parameters);
        return $this->replaceWildcards($pattern, $parameters);
    }
    /**
     * Replace all the wildcard parameters for $pattern.
     *
     * @param  string  $pattern
     * @param  array  $parameters
     *
     * @return string
     */
    public function replaceWildcards(string $pattern, array &$parameters) : string
    {
        $pattern = preg_replace_callback('/\{.*?\}/', function ($match) use (&$parameters) {
            return (empty($parameters) && ! (substr($match[0], -strlen('?}')) === '?}'))
                ? $match[0]
                : array_shift($parameters);
        }, $pattern);
        return trim(preg_replace('/\{.*?\?\}/', '', $pattern), '/');
    }

    /**
     * Replace all the named parameters in $pattern.
     *
     * @param  string  $pattern
     * @param  array
     *
     * @return string
     */
    public function replaceNamed(string $pattern, &$parameters) : string
    {
        return preg_replace_callback('/\{(.*?)\??\}/', function ($m) use (&$parameters) {
            if (isset($parameters[$m[1]])) {
                $value = $parameters[$m[1]] ?: null;
                unset($parameters[$m[1]]);
                return $value;
            }
            return $m[0];
        }, $pattern);
    }
}