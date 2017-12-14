<?php

namespace Ems\Core;

use Ems\Core\Exceptions\UnsupportedParameterException;
use OutOfBoundsException;

/**
 * This Trait if for easy implementation of.
 *
 * @see \Ems\Contracts\Core\Configurable
 *
 * You just have to add an array of $defaultOptions
 * to your class
 **/
trait ConfigurableTrait
{
    /**
     * @var array
     **/
    protected $_options = [];

    /**
     * {@inheritdoc}
     *
     * @param string $key
     *
     * @throws \Ems\Contracts\Core\Errors\Unsupported
     *
     * @return mixed
     **/
    public function getOption($key)
    {
        $this->checkOptionKey($key);

        return isset($this->_options[$key]) ? $this->_options[$key] : $this->defaultOptions[$key];
    }

    /**
     * {@inheritdoc}
     *
     * @param string $key
     * @param mixed  $value
     *
     * @throws \Ems\Contracts\Core\Errors\Unsupported
     *
     * @return self
     **/
    public function setOption($key, $value)
    {
        $this->_options[$this->checkOptionKey($key)] = $value;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     **/
    public function supportedOptions()
    {
        return array_keys($this->getDefaultOptions());
    }

    /**
     * {@inheritdoc}
     *
     * @param string|array $keys (optional)
     *
     * @throws \Ems\Contracts\Core\Errors\Unsupported
     *
     * @return self
     **/
    public function resetOptions($keys = null)
    {
        $keys = $keys ? (array) $keys : $this->supportedOptions();
        foreach ($keys as $key) {
            $this->checkOptionKey($key);
            if (isset($this->_options[$key])) {
                unset($this->_options[$key]);
            }
        }

        return $this;
    }

    /**
     * This is a helper method for classes which support manual
     * passed options to overwrite internal options.
     *
     * @param string $key
     * @param array $options
     *
     * @return mixed
     *
     * @throws \Ems\Contracts\Core\Errors\Unsupported
     */
    protected function mergeOption($key, array $options)
    {
        return isset($options[$key]) ? $options[$key] : $this->getOption($key);
    }

    /**
     * This is a helper method for classes which support manual
     * passed options to overwrite internal options.
     *
     * @param array $options
     *
     * @return mixed
     *
     * @throws \Ems\Contracts\Core\Errors\Unsupported
     */
    protected function mergeOptions(array $options)
    {
        $merged = [];
        foreach ($this->supportedOptions() as $option) {
            $merged[$option] = $this->mergeOption($option, $options);
        }

        return $merged;
    }

    /**
     * Check if $key is supported, otherwise throw an Exception.
     *
     * @param string $key
     *
     * @return string
     **/
    protected function checkOptionKey($key)
    {
        if (!$this->optionExists($key)) {
            throw new UnsupportedParameterException("Option '$key' is not supported");
        }
        return $key;
    }

    /**
     * @param string $key
     *
     * @return bool
     **/
    protected function optionExists($key)
    {
        $defaultOptions = $this->getDefaultOptions();

        return isset($defaultOptions[$key]);
    }

    /**
     * Get the default option. Throw an exception if the class
     * which uses this trait has implemented a $defaultOptions
     * property.
     *
     * @return array
     **/
    protected function getDefaultOptions()
    {
        if (!isset($this->defaultOptions)) {
            throw new OutOfBoundsException(get_class($this).' has to define a property named $defaultOptions');
        }

        return $this->defaultOptions;
    }
}
