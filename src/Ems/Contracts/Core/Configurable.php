<?php

namespace Ems\Contracts\Core;

/**
 * This is a basic interface for configurable
 * objects. A compiler, parser or something like
 * that are typical use cases.
 **/
interface Configurable
{
    /**
     * Get the value for option $key. You have to throw an Unsupported
     * if the key is not none.
     *
     * @param string $key
     *
     * @throws \Ems\Contracts\Core\Unsupported
     *
     * @return mixed
     **/
    public function getOption($key);

    /**
     * Set the option $key ti $value. Throw an Unsupported if the
     * key is not none.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @throws \Ems\Contracts\Core\Unsupported
     *
     * @return self
     **/
    public function setOption($key, $value);

    /**
     * Return an array of supported option keys.
     *
     * @return array
     **/
    public function supportedOptions();

    /**
     * Reset option(s) to its default value(s).  Pass no
     * key to reset all options, pass a string for one option
     * and an array for many options. If any unknownn keys are
     * passed throw an Unsupported.
     *
     * @param string|array $keys (optional)
     *
     * @throws \Ems\Contracts\Core\Unsupported
     *
     * @return self
     **/
    public function resetOptions($keys = null);
}
