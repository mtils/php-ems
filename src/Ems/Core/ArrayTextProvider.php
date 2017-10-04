<?php

namespace Ems\Core;

use Ems\Contracts\Core\Multilingual;
use Ems\Contracts\Core\TextProvider;
use Ems\Core\Support\TextProviderTrait;

class ArrayTextProvider implements TextProvider, Multilingual
{
    use TextProviderTrait;

    /**
     * @var array
     **/
    protected $translations;

    /**
     * @param array  $translations (optional)
     * @param string $domain     (optional)
     * @param string $namespace  (optional)
     **/
    public function __construct(array $translations = [], $domain = '', $namespace = '', $isRoot = true)
    {
        $this->translations = $translations ? $translations : null;
        $this->domain = trim($domain, '.');
        $this->namespace = trim($namespace, ':');
        $this->buildPrefix($this->namespace, $this->domain);
        $this->isRoot = $isRoot;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $key
     * @param string $locale (optional)
     *
     * @return bool
     **/
    public function has($key)
    {
        return $this->translator->has($this->composeKey($key), $this->locale);
    }

    public function setTranslations($lang, $group, $messages, $namespace=null)
    {

        if (!isset($this->translations[$lang])) {
            $this->translations[$lang] = [];
        }
        
        if (!isset($this->translations[$lang])) {
            $this->translations[$lang] = [];
        }

        if (!isset($this->translations[$lang][$group])) {
            $this->translations[$lang][$group] = [];
        }

        $this->translations[$lang][$group] = $messages;

        return $this;

    }

    protected function loadIfNotLoaded($key)
    {
        if (!isset($this->translations[$this->locale])) {
            $this->translations[$this->locale] = [];
        }
    }
}
