<?php

namespace Ems\Core\Laravel;

use Ems\Contracts\Core\Multilingual;
use Ems\Contracts\Core\TextProvider;
use Ems\Core\Support\TextProviderTrait;
use Illuminate\Translation\Translator;

class TranslatorTextProvider implements TextProvider, Multilingual
{
    use TextProviderTrait;

    /**
     * @var \Illuminate\Translation\Translator
     **/
    protected $translator;

    /**
     * @var string
     **/
    protected $namespace = '';

    /**
     * @param \Illuminate\Translation\Translator $translator
     * @param string                             $domain     (optional)
     * @param string                             $namespace  (optional)
     * @param bool                               $isRoot (default:true)
     **/
    public function __construct(Translator $translator, $domain = '', $namespace = '', $isRoot = true)
    {
        $this->translator = $translator;
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

    /**
     * {@inheritdoc}
     *
     * @param string $key
     * @param array  $replace (optional)
     * @param string $locale  (optional)
     *
     * @return string
     **/
    public function get($key, array $replace = [])
    {
        return $this->translator->get($this->composeKey($key), $replace, $this->locale);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $key
     * @param int    $number
     * @param array  $replace (optional)
     * @param string $locale  (optional)
     *
     * @return string
     **/
    public function choice($key, $number, array $replace = [])
    {
        return $this->translator->choice($this->composeKey($key), $number, $replace, $this->locale);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     **/
    public function getLocale()
    {
        return $this->isRoot ? $this->translator->getLocale() : $this->locale;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $locale
     *
     * @return self
     **/
    public function setLocale($locale)
    {
        if ($this->isRoot) {
            $this->translator->setLocale($locale);

            return $this;
        }

        $this->locale = $locale;

        return $this;
    }

    /**
     * Return the current namespace (if one set).
     *
     * @return string
     **/
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * Return a new TextProvider with a predefined namespace.
     *
     * @param string
     *
     * @return self
     **/
    public function forNamespace($namespace)
    {
        return $this->replicate(['namespace' => $namespace]);
    }

    /**
     * Returns a new instance of this TextProvider.
     *
     * @param array $properties
     *
     * @return self
     **/
    protected function replicate(array $properties=[])
    {
        $class = get_class($this);
        $domain = isset($properties['domain']) ? $properties['domain'] : $this->domain;
        $namespace = isset($properties['namespace']) ? $properties['namespace'] : $this->namespace;

        return new $class($this->translator, $domain, $namespace, false);
    }

}
