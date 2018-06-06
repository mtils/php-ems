<?php


namespace Ems\Core\Support;


trait TextProviderTrait
{
    use MultilingualTrait;

    /**
     * @var string
     **/
    protected $domain = '';

    /**
     * @var string
     **/
    protected $keyPrefix = '';

    /**
     * @var bool
     **/
    protected $isRoot = true;

    /**
     * {@inheritdoc}
     *
     * @param string $domain
     *
     * @throws \Ems\Contracts\Core\Errors\NotFound
     *
     * @return self
     **/
    public function forDomain($domain)
    {
        return $this->replicate(['domain' => $domain]);
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
     * Returns a new instance of this TextProvider.
     *
     * @param array $properties
     *
     * @return self
     **/
    protected function replicate(array $properties=[])
    {
        $class = get_class($this);
        return new $class(isset($properties['domain']) ? $properties['domain'] : null);
    }

    /**
     * Build the key if a domain or namespace was set.
     *
     * @param string $key
     *
     * @return string
     **/
    protected function composeKey($key)
    {
        $key = !$this->keyPrefix ? $key : $this->keyPrefix.$key;
        return $key;
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
