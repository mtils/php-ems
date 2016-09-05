<?php


namespace Ems\Core;

use Ems\Contracts\Core\TextParser;


class TextParserQueue implements TextParser
{

    /**
     * All assigned parsers
     *
     * @var array
     **/
    protected $parsers = [];

    /**
     * An spl_object_hash array to ignore already added parsers
     *
     * @var array
     **/
    protected $parserIds = [];

    /**
     * {@inheritdoc}
     *
     * @param string $text
     * @param array $data The view variables
     * @param bool $purgePlaceholders (optional)
     * @return string
     **/
    public function parse($text, array $data, $purgePlaceholders=true)
    {
        foreach ($this->parsers as $parser) {
            $text = $parser->parse($text, $data, false);
        }
        return $purgePlaceholders ? $this->purge($text) : $text;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $text
     * @return string The purged text
     **/
    public function purge($text)
    {
        foreach ($this->parsers as $parser) {
            $text = $parser->purge($text);
        }
        return $text;
    }

    /**
     * Add a new parser to the render queue
     *
     * @param \Ems\Contracts\Core\TextParser $parser
     * @return self
     **/
    public function add(TextParser $parser)
    {

        $objectHash = $this->objectHash($parser);

        if (isset($this->parserIds[$objectHash])) {
            return $this;
        }

        $this->parsers[] = $parser;

        $this->parserIds[$objectHash] = true;

        return $this;
    }

    /**
     * Remove a parser from the render queue
     *
     * @param \Ems\Contracts\Core\TextParser $parser
     * @return self
     **/
    public function remove(TextParser $parser)
    {

        $parserHash = $this->objectHash($parser);

        $this->parsers = array_filter($this->parsers, function($known) use ($parserHash) {
            return ($this->objectHash($known) != $parserHash);
        });

        if (isset($this->parserIds[$parserHash])) {
            unset($this->parserIds[$parserHash]);
        }

        return $this;

    }

    /**
     * Return an id to intentify one instance of an object
     *
     * @param object
     * @return string
     **/
    protected function objectHash($object)
    {
        return spl_object_hash($object);
    }

}
