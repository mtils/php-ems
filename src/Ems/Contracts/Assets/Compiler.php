<?php


namespace Ems\Contracts\Assets;

use Ems\Contracts\Core\TextParser;

interface Compiler
{

    /**
     * Compile the Collection and return the compiled string
     * Pass the name of the parsers in $parserNames
     * Pass any parserOptions via [$parserName=>[$foo=>$bar]]
     * The parser should pass a bunch of variables in the parser options
     * for a specific TextParser. (The current file, collection, asset, ...
     * So that parsers can work with the data if they need to
     *
     * @param \Ems\Contracts\Assets\Collection $collection
     * @param array $parserNames (optional)
     * @param array $parserOptions (optional)
     * @return string
     **/
    public function compile(Collection $collection, array $parserNames=[], array $parserOptions=[]);

    /**
     * Returns the names of all assigned parsers
     *
     * @return array
     **/
    public function parserNames();

    /**
     * Returns the parser with name $name
     *
     * @param string $name
     * @return \Ems\Contracts\Core\TextParser
     * @throws \Ems\Contracts\Core\NotFound
     **/
    public function parser($name);

    /**
     * Add a parser with name $name
     *
     * @param string $name
     * @param \Ems\Contracts\Core\TextParser $parser
     * @return self
     **/
    public function addParser($name, TextParser $parser);

    /**
     * Remove the parser with name $name
     *
     * @param string
     * @return self
     * @throws \Ems\Contracts\Core\NotFound
     **/
    public function removeParser($name);

    /**
     * Get informed when assets where compiled. All compile() parameters
     * will be passed and the renderer:
     *
     * signature is: function (Collection $collection, $renderedContent, $parserNames, $parserOptions) {
     *               }
     *
     * If you return something trueish this will be used instead of the contents
     *
     * @param callable $listener
     * @return self
     **/
    public function whenCompiled(callable $listener);

}
