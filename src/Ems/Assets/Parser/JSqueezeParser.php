<?php


namespace Ems\Assets\Parser;


use Ems\Contracts\Core\TextParser;
use Patchwork\JSqueeze;

class JSqueezeParser implements TextParser
{

    /**
     * @var \Patchwork\JSqueeze
     **/
    protected $minifier;

    protected $defaultOptions = [
        'singleLine'            => true,
        'keepImportantComments' => true,
        'specialVarRx'          => false
    ];

    public function __construct(JSqueeze $minifier=null)
    {
        $this->minifier = $minifier ?: new JSqueeze;
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

        return $this->minifier->squeeze(
            $text,
            $options['singleLine'],
            $options['keepImportantComments'],
            $options['specialVarRx']
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
