<?php

namespace Ems\Core\Support;

use Ems\Contracts\Core\NamedCallableChain;
use Closure;

trait ProvidesNamedCallableChain
{
    /**
     * @var \Ems\Contracts\Core\NamedCallableChain
     **/
    protected $parent;

    /**
     * @var array
     **/
    protected $chain = [];

    /**
     * @var array
     **/
    protected $callables = [];

    /**
     * {@inheritdoc}
     *
     * @return array
     **/
    public function getChain()
    {
        return $this->chain;
    }

    /**
     * {@inheritdoc}
     *
     * @param string|array $chain
     *
     * @return self (same instance)
     **/
    public function setChain($chain)
    {
        $chain = func_num_args() > 1 ? func_get_args() : $chain;
        $this->chain = $this->parseChain($chain);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param string|array $names
     *
     * @return self (New instance)
     **/
    public function with($names)
    {
        $newChain = func_num_args() > 1 ? func_get_args() : $names;

        return (new static())->setNativeChain($this->buildChain($newChain))
                             ->setParent($this->parent ? $this->parent : $this);
    }

    /**
     * {@inheritdoc}
     *
     * @param string   $name
     * @param callable $callable
     *
     * @return self (same instance)
     **/
    public function extend($name, callable $callable)
    {
        if ($this->parent) {
            $this->parent->extend($name, $callable);
        }

        if ($callable instanceof Closure) {
            $callable = $callable->bindTo($this);
        }

        $this->callables[$name] = $callable;

        return $this;
    }

    /**
     * Return the parent NamedCallableChain if this one was forked.
     *
     * @return \Ems\Contracts\Core\NamedCallableChain
     **/
    public function getParent()
    {
        return $this->parent;
    }

    /**
     *  Set the parent chain.
     *
     * @param \Ems\Contracts\Core\NamedCallableChain $parent
     *
     * @return self
     **/
    public function setParent(NamedCallableChain $parent)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Return all extensions (callables).
     *
     * @return array
     **/
    public function extensions()
    {
        if ($this->parent) {
            return $this->parent->extensions();
        }

        return $this->callables;
    }

    /**
     * @param string $name
     *
     * @return callable
     **/
    public function getExtension($name)
    {
        if ($this->parent) {
            return $this->parent->getExtension($name);
        }

        return $this->callables[$name];
    }

    /**
     * Return if an extension with name $name exists.
     *
     * @param string $name
     *
     * @return bool
     **/
    public function hasExtension($name)
    {
        $extensions = $this->extensions();

        return isset($extensions[$name]);
    }

    /**
     * Call an extension.
     *
     * @param string $name
     * @param array  $params (optional)
     *
     * @return mixed
     **/
    public function callExtension($name, array $params = [])
    {
        $extension = $this->getExtension($name);

        // call_user_func_array seems to be slow
        switch (count($params)) {
            case 0:
                return call_user_func($extension);
            case 1:
                return call_user_func($extension, $params[0]);
            case 2:
                return call_user_func($extension, $params[0], $params[1]);
            case 3:
                return call_user_func($extension, $params[0], $params[1], $params[2]);
            case 4:
                return call_user_func($extension, $params[0], $params[1], $params[2], $params[3]);
            default:
                return call_user_func_array($extension, $params);
        }
    }

    /**
     * Set the chain in its native format.
     *
     * @param array $chain
     *
     * @return self
     **/
    public function setNativeChain(array $chain)
    {
        $this->chain = $chain;

        return $this;
    }

    protected function buildChain($merge = [])
    {
        $newChain = $merge ? $this->parseChain($merge) : [];

        $merged = [];

        foreach ($this->chain as $name => $infos) {
            if (!isset($newChain[$name])) {
                $merged[$name] = $infos;
                continue;
            }

            if ($newChain[$name]['operator'] == '-') {
                continue;
            }

            $merged[$name] = $newChain[$name];
        }

        foreach ($newChain as $name => $infos) {
            if ($infos['operator'] == '-') {
                continue;
            }

            if (!isset($merged[$name])) {
                $merged[$name] = $infos;
            }
        }

        return $merged;
    }

    /**
     * Cut the ! from the string.
     *
     * @param string $name
     *
     * @return array
     **/
    protected function splitExpression($name)
    {
        if (strpos($name, '!') === 0) {
            return ['-', ltrim($name, '!')];
        }

        return ['+', $name];
    }

    /**
     * Parses a passed chain into a native format.
     *
     * @param string|array $chain
     *
     * @return array
     **/
    protected function parseChain($chain)
    {
        $parts = is_array($chain) ? $chain : explode('|', $chain);
        $parsed = [];

        foreach ($parts as $name) {
            list($operator, $key) = $this->splitExpression($name);

            $dotPos = strpos($key, ':');

            list($key, $parameters) = $dotPos ? explode(':', $key, 2) : [$key, ''];

            $parsed[$key] = [
                'operator'   => $operator,
                'parameters' => $parameters ? explode(',', $parameters) : [],
            ];
        }

        return $parsed;
    }
}
