<?php
/**
 *  * Created by mtils on 05.06.18 at 06:22.
 **/

namespace Ems\Core;


use ArrayAccess;
use Ems\Contracts\Core\Multilingual;
use Ems\Contracts\Core\TextProvider;
use Ems\Core\Support\TextProviderTrait;
use function str_replace;

class ArrayTextProvider implements TextProvider, Multilingual
{
    use TextProviderTrait;

    /**
     * @var array|ArrayAccess
     */
    protected $data = [];

    /**
     * @var string
     */
    protected $placeholderPrefix = ':';

    /**
     * @var string
     */
    protected $placeholderSuffix = '';

    /**
     * ArrayTextProvider constructor.
     *
     * @param array $data
     * @param string $domain (optional)
     */
    public function __construct(array $data=[], $domain='')
    {
        $this->data = $data;
        $this->domain = $domain;
        $this->buildPrefix('', $domain);
    }

    /**
     * @inheritDoc
     */
    public function has($key)
    {
        $locale = $this->getLocale();
        $path = $this->composeKey($key);
        return isset($this->data["$locale.$path"]);
    }

    /**
     * @inheritDoc
     */
    public function get($key, array $replace = [])
    {
        $path = $this->composeKey($key);
        return $this->replacePlaceholders($this->findText($path), $replace);

    }

    /**
     * @inheritDoc
     *
     * Currently only pipes are supported!
     * One item: "There is one apple"
     *     means a quantity of one
     * Two items: "There is one apple|There are many apples"
     *     means quantity one, quantity many
     * Three items: "There are no apples|There is one apple|There are many apples"
     *     means: quantity null, quantity one, quantity many
     */
    public function choice($key, $number, array $replace = [])
    {
        $translations = explode('|', $this->get($key));

        if (count($translations) == 1) {
            return $this->replacePlaceholders($translations[0], $replace);
        }

        if (count($translations) == 2) {
            return $this->replacePlaceholders($translations[$number < 2 ? 0 : 1], $replace);
        }

        if (!$number) {
            return $this->replacePlaceholders($translations[0], $replace);
        }

        return $this->replacePlaceholders($translations[$number < 2 ? 1 : 2], $replace);

    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set all text data. It must be indexed by [$lang][$key]
     *
     * @param array|ArrayAccess $data
     *
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @param string $line
     * @param array $replace
     *
     * @return string
     */
    protected function replacePlaceholders($line, array $replace)
    {
        if (!$replace) {
            return $line;
        }

        foreach ($replace as $placeholder=>$replacement) {
            $line = str_replace($this->placeholderPrefix.$placeholder.$this->placeholderSuffix, $replacement, $line);
        }
        return $line;
    }

    /**
     * @param string $path
     *
     * @return string
     */
    protected function findText($path)
    {
        foreach ($this->localeSequence() as $locale) {
            $arrayKey = "$locale.$path";
            if (isset($this->data[$arrayKey])) {
                return $this->data[$arrayKey];
            }
        }
        return $path;
    }

    /**
     * @param array $properties
     *
     * @return $this
     */
    protected function replicate(array $properties = [])
    {
        $fork = new static($this->data, isset($properties['domain']) ? $properties['domain'] : '');
        $locale = isset($properties['locale']) ? $properties['locale'] : $this->locale;
        $fork->setFallbacks($this->getFallbacks());
        return $fork->setLocale($locale);
    }
}