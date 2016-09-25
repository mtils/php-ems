<?php


namespace Ems\Assets\Parser;


use Ems\Contracts\Core\TextParser;


class CssMinParser implements TextParser
{

    /**
     * @var \CssMin
     **/
    protected $minifier;

    protected $defaultOptions = [
    ];

    public function __construct(callable $minifier=null)
    {

        $this->minifier = $minifier ?: function ($source, $filters=[], $plugins=[]) {
            return \CssMin::minify($source, $filters, $plugins);
        };
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

        return call_user_func($this->minifier,
            $text, [], []
        );

//         $options = $this->mergeOptions($config);

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
