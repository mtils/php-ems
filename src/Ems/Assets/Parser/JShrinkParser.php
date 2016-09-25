<?php


namespace Ems\Assets\Parser;


use Ems\Contracts\Core\TextParser;
use JShrink\Minifier;

class JShrinkParser implements TextParser
{

    /**
     * @var \JShrink\Minifier
     **/
    protected $minifier;

    protected $defaultOptions = [
        'flaggedComments'       => true
    ];

    public function __construct(Minifier $minifier=null)
    {
        $this->minifier = $minifier ?: new Minifier;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $text
     * @param array $config The configuration options
     * @param bool $purgePlaceholders (optional)
     * @return string
     **/
    public function parse($text, array $config, $purgePlaceholders=true)
    {

        $options = $this->mergeOptions($config);

        return $this->minifier->minify(
            $text,
            $options
        );
    }

    /**
     * {@inheritdoc}
     *
     * @param string $text
     * @return string The purged text
     **/
    public function purge($text)
    {
        return $text;
    }

    /**
     * Merges the passed options with the default option
     *
     * @param array $passedOptions
     * @return arras
     **/
    protected function mergeOptions(array $passedOptions)
    {
        return array_merge($this->defaultOptions, $passedOptions);
    }
}
