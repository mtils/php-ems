<?php


namespace Ems\Core\Laravel;


use Ems\Contracts\Core\Multilingual;
use Ems\Contracts\Core\TextProvider;
use Illuminate\Translation\Translator;

class TranslatorTextProvider implements TextProvider, Multilingual
{

    /**
     * @var \Illuminate\Translation\Translator
     **/
    protected $translator;

    /**
     * @var string
     **/
    protected $domain = '';

    /**
     * @var string
     **/
    protected $namespace = '';

    /**
     * @var string
     **/
    protected $keyPrefix = '';

    /**
     * @var string
     **/
    protected $locale;

    /**
     * @var bool
     **/
    protected $isRoot = true;

    /**
     * @param \Illuminate\Translation\Translator $translator
     * @param string $domain (optional)
     * @param string $namespace (optional)
     **/
    public function __construct(Translator $translator, $domain='', $namespace='', $isRoot=true)
    {
        $this->translator = $translator;
        $this->domain = trim($domain, '.');
        $this->namespace = trim($namespace,':');
        $this->buildPrefix($this->namespace, $this->domain);
        $this->isRoot = $isRoot;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $key
     * @param string $locale (optional)
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
     * @param array $replace (optional)
     * @param string $locale (optional)
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
     * @param int $number
     * @param array $replace (optional)
     * @param string $locale (optional)
     * @return string
     **/
    public function choice($key, $number, array $replace = [])
    {
        return $this->translator->choice($this->composeKey($key), $number, $replace, $this->locale);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $domain
     * @return self
     * @throws \Ems\Core\NotFound
     **/
    public function forDomain($domain)
    {
        return $this->replicate($domain, $this->namespace);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     **/
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * Return a new instance of this provider for locale $locale
     *
     * @param string $locale
     * @return self
     **/
    public function forLocale($locale)
    {
        return $this->replicate($this->domain, $this->namespace)->setLocale($locale);
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
     * @return self
     **/
    public function setLocale($locale)
    {
        if ($this->isRoot) {
            $this->translator->setlocale($locale);
            return $this;
        }

        $this->locale = $locale;

        return $this;
    }

    /**
     * Return the current namespace (if one set)
     *
     * @return string
     **/
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * Return a new TextProvider with a predefinied namespace
     *
     * @param string
     * @return self
     **/
    public function forNamespace($namespace)
    {
        return $this->replicate($this->domain, $namespace);
    }

    /**
     * Returns a new instance of this TextProvider.
     *
     * @param string $domain
     * @param string $namespace
     * @return self
     **/
    protected function replicate($domain, $namespace)
    {
        return new static($this->translator, $domain, $namespace, false);
    }

    /**
     * Build the key if a domain or namespace was set
     *
     * @param string $key
     * return string
     **/
    protected function composeKey($key)
    {
        return !$this->keyPrefix ? $key : $this->keyPrefix . $key;
    }

    /**
     * Set the key prefix. This is a one timer and will only be done once at
     * construction of this object
     *
     * @param string $namespace
     * @param string $domain
     **/
    protected function buildPrefix($namespace, $domain)
    {
        if ($namespace) {
            $this->keyPrefix = "$namespace::";
        }

        if ($domain) {
            $this->keyPrefix .= "$domain.";
        }
    }
}
