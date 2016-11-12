<?php

namespace Ems\Assets;

use Ems\Contracts\Assets\Asset as AssetContract;
use Ems\Contracts\Core\Filesystem;
use Ems\Contracts\Assets\Compiler as CompilerContract;
use Ems\Contracts\Core\TextParser;
use Ems\Contracts\Assets\Collection as CollectionContract;
use Ems\Contracts\Assets\Registry as RegistryContract;
use Ems\Core\Exceptions\HandlerNotFoundException;

class Compiler implements CompilerContract
{
    /**
     * @var \Ems\Contracts\Assets\Registry
     **/
    protected $registry;

    /**
     * @var \Ems\Contracts\Core\Filesystem
     **/
    protected $files;

    /**
     * @var array
     **/
    protected $parsers = [];

    /**
     * @var callable
     **/
    protected $compiledListener;

    /**
     * @param \Ems\Contracts\Core\Filesystem $files
     * @param \Ems\Contracts\Assets\Registry $registry
     **/
    public function __construct(Filesystem $files, RegistryContract $registry)
    {
        $this->files = $files;
        $this->registry = $registry;
        $this->compiledListener = function ($collection, $content, $parserNames, $parserOptions) {};
    }

    /**
     * {@inheritdoc}
     *
     * If you pass $parserOptions['*'] = [] the vars will applied to all parsers
     *
     * @param \Ems\Contracts\Assets\Collection $collection
     * @param array                            $parserNames   (optional)
     * @param array                            $parserOptions (optional)
     *
     * @return string
     **/
    public function compile(CollectionContract $collection, array $parserNames = [], array $parserOptions = [])
    {
        if (!$parserNames) {
            return $this->callListenerAndReturnContent($collection, $this->readContents($collection), $parserNames, $parserOptions);
        }

        $parsers = $this->collectParsers($parserNames);

        $allContent = '';
        $nl = '';

        foreach ($collection as $asset) {
            $allContent .= $nl.$this->runParserQueue($asset, $collection, $parsers, $parserOptions);
            $nl = "\n";
        }

        return $this->callListenerAndReturnContent($collection, $allContent, $parserNames, $parserOptions);
    }

    /**
     * Returns the names of all assigned parsers.
     *
     * @return array
     **/
    public function parserNames()
    {
        return array_keys($this->parsers);
    }

    /**
     * Returns the parser with name $name.
     *
     * @param string $name
     *
     * @return \Ems\Contracts\Core\TextParser
     *
     * @throws \Ems\Contracts\Core\Errors\NotFound
     **/
    public function parser($name)
    {
        if (isset($this->parsers[$name])) {
            return $this->parsers[$name];
        }

        throw new HandlerNotFoundException("Parser $name not found, perhaps the package of this parser is not installed");
    }

    /**
     * Add a parser with name $name.
     *
     * @param string                         $name
     * @param \Ems\Contracts\Core\TextParser $parser
     *
     * @return self
     **/
    public function addParser($name, TextParser $parser)
    {
        $this->parsers[$name] = $parser;

        return $this;
    }

    /**
     * Remove the parser with name $name.
     *
     * @param string
     *
     * @return self
     *
     * @throws \Ems\Contracts\Core\Errors\NotFound
     **/
    public function removeParser($name)
    {
        // Trigger exception
        $parser = $this->parser($name);
        unset($this->parsers[$name]);

        return $this;
    }

    /**
     * Get informed when assets where compiled. The CompilerConfig will
     * be passed.
     *
     * @param callable $listener
     *
     * @return self
     **/
    public function whenCompiled(callable $listener)
    {
        $this->compiledListener = $listener;

        return $this;
    }

    protected function runParserQueue(AssetContract $asset, CollectionContract $collection, $parsers, $parserOptions)
    {
        $file = $this->absolutePath($collection, $asset);

        $content = $this->files->contents($file);

        foreach ($parsers as $name => $parser) {
            $options = $this->parseOptions($collection, $name, $asset, $file, $parserOptions);
            $content = $parser->parse($content, $options, false);
        }

        foreach ($parsers as $name => $parser) {
            $content = $parser->purge($content);
        }

        return $content;
    }

    protected function callListenerAndReturnContent(CollectionContract $collection, $content, array $parserNames, array $parserOptions)
    {
        if (!$newContent = call_user_func($this->compiledListener, $collection, $content, $parserNames, $parserOptions)) {
            return $content;
        }
        if (is_string($newContent)) {
            return $newContent;
        }

        return $content;
    }

    protected function collectParsers(array $parserNames)
    {
        $parsers = [];
        foreach ($parserNames as $name) {
            $parsers[$name] = $this->parser($name);
        }

        return $parsers;
    }

    protected function readContents(CollectionContract $collection)
    {
        $allContent = '';
        $nl = '';

        foreach ($collection as $asset) {
            $allContent .= $nl.$this->files->contents($this->absolutePath($collection, $asset));
            $nl = "\n";
        }

        return $allContent;
    }

    protected function absolutePath(CollectionContract $collection, AssetContract $asset)
    {
        return $this->registry
                    ->to($collection->group())
                    ->absolute($asset->name());
    }

    protected function parseOptions(CollectionContract $collection, $parserName, AssetContract $asset, $file, $parserOptions)
    {
        $options = isset($parserOptions[$parserName]) ? $parserOptions[$parserName] : [];

        $baseOptions = [
            'collection' => $collection,
            'parser_name' => $parserName,
            'asset' => $asset,
            'file_path' => $file,
        ];

        if (!isset($parserOptions['*'])) {
            $parserOptions['*'] = [];
        }

        return array_merge($baseOptions, $options, $parserOptions['*']);
    }
}
