<?php


namespace Ems\Core\Support;


trait TextProviderTrait
{
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
     * {@inheritdoc}
     *
     * @param string $domain
     *
     * @throws \Ems\Core\Errors\NotFound
     *
     * @return self
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
     * Return a new instance of this provider for locale $locale.
     *
     * @param string $locale
     *
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
        return $this->locale;
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
     * Return a new TextProvider with a predefinied namespace.
     *
     * @param string
     *
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
     *
     * @return self
     **/
    protected function replicate($domain, $namespace)
    {
        $class = get_class($this);
        return new $class($domain, $namespace, false);
    }

    /**
     * Build the key if a domain or namespace was set.
     *
     * @param string $key
     *                    return string
     **/
    protected function composeKey($key)
    {
        return !$this->keyPrefix ? $key : $this->keyPrefix.$key;
    }

    /**
     * Set the key prefix. This is a one timer and will only be done once at
     * construction of this object.
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
