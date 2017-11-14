<?php

namespace Ems\Core\Patterns;

use Ems\Core\Exceptions\HandlerNotFoundException;
use Ems\Core\Helper;

/**
 * @see \Ems\Contracts\Core\Extendable
 **/
trait ExtendableTrait
{
    /**
     * Here the callables are held.
     *
     * @var array
     **/
    protected $_extensions = [];

    /**
     * {@inheritdoc}
     *
     * @param string   $name
     * @param callable $callable
     *
     * @return self
     **/
    public function extend($name, callable $callable)
    {
        $this->_extensions[$name] = $callable;

        return $this;
    }

    /**
     * Return the extension named $name.
     *
     * @param string $name
     *
     * @return mixed
     **/
    public function getExtension($name)
    {
        if ($this->hasExtension($name)) {
            return $this->_extensions[$name];
        }

        throw new HandlerNotFoundException(get_class($this).": No extension named \"$name\" found");
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     **/
    public function extensions()
    {
        return array_keys($this->_extensions);
    }

    /**
     * Return if an extension with name $name exists.
     *
     * @param string $name
     *
     * @return bool
     **/
    protected function hasExtension($name)
    {
        return isset($this->_extensions[$name]);
    }

    /**
     * Call all extensions until one returns not null and return the result
     *
     * @param mixed $args
     *
     * @return mixed
     **/
    protected function callUntilNotNull($args=[])
    {
        foreach ($this->extensions() as $name) {
            $result = $this->callExtension($name, $args);

            if ($result !== null) {
                return $result;
            }
        }

        return null;
    }

    /**
     * Call the extension named $name with $params.
     *
     * @param string $name
     * @param mixed $params (optional)
     *
     * @return mixed
     **/
    protected function callExtension($name, $params = [])
    {
        return Helper::call($this->getExtension($name), $params);
    }

    /**
     * This method is for collecting extensions WHICH NAMES are patterns.
     *
     * @param string $name
     *
     * @return array
     **/
    protected function collectExtensions($name)
    {

        $extensions = [];

        foreach ($this->extensions() as $pattern) {
            if ($this->patternMatches($pattern, $name)) {
                $extensions[] = $this->getExtension($pattern);
            }
        }

        return $extensions;

    }

    /**
     * Select extensions WHICH NAMES are pattern. If none found throw an exception.
     *
     * @see self:collectExtensionsOrFail()
     *
     * @param string $name
     *
     * @return array
     **/
    protected function collectExtensionsOrFail($name)
    {
        if (!$extensions = $this->collectExtensions($name)) {
            throw new HandlerNotFoundException("No extensions found matching the name '$name'.");
        }
        return $extensions;
    }

    /**
     * Match a string on a pattern
     *
     * @param string
     * @param pattern
     *
     * @return bool
     **/
    protected function patternMatches($pattern, $string)
    {
        return fnmatch($pattern, $string, FNM_NOESCAPE);
    }
}
