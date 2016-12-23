<?php

namespace Ems\Assets;

use Ems\Contracts\Assets\Manager as ManagerContract;
use Ems\Contracts\Assets\NameAnalyser;

class ManagerProxy implements ManagerContract
{
    /**
     * @var \Ems\Contracts\Assets\Manager
     **/
    protected $parent;

    /**
     * @var \Ems\Contracts\Assets\NameAnalyser
     **/
    protected $namer;

    /**
     * @var string
     **/
    protected $groupPrefix = '';

    /**
     * @param \Ems\Contracts\Assets\Manager      $parent
     * @param \Ems\Contracts\Assets\NameAnalyser $namer
     * @param string                             $groupPrefix
     **/
    public function __construct(ManagerContract $parent, NameAnalyser $namer, $groupPrefix = '')
    {
        $this->parent = $parent;
        $this->namer = $namer;
        $this->groupPrefix = $groupPrefix;
    }

    /**
     * {@inheritdoc}
     *
     * @param string|array $asset
     * @param string       $group (optional)
     *
     * @return self
     **/
    public function import($asset, $group = null)
    {
        $this->parent->import($asset, $this->mergePassedGroup($asset, $group));

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $asset
     * @param string $content
     * @param string $group   (optional)
     *
     * @return self
     **/
    public function inline($asset, $content, $group = null)
    {
        $this->parent->inline($asset, $content, $this->mergePassedGroup($asset, $group));

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $asset
     * @param string $group   (optional)
     * @param string $content (optional) (make it a inline asset)
     *
     * @return \Ems\Contracts\Assets\Asset
     **/
    public function newAsset($asset, $group = null, $content = '')
    {
        return $this->parent->newAsset($asset, $this->mergePassedGroup($asset, $group), $content);
    }

    /**
     * {@inheritdoc}
     *
     * @param string   $asset
     * @param callable $handler
     *
     * @return self
     **/
    public function on($asset, callable $handler)
    {
        $this->parent->on($asset, $handler);

        return $this;
    }

    /**
     * {@inheritdoc}
     * An after sorting is just handled as an reversed before sorting,
     * because only befores are working the right way.
     *
     * @param string $asset (optional)
     *
     * @return self
     **/
    public function after($asset = null)
    {
        $this->parent->after($asset);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $asset (optional)
     *
     * @return self
     **/
    public function before($asset = null)
    {
        $this->parent->before($asset);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $group
     *
     * @return \Ems\Contracts\Assets\Collection
     **/
    public function render($group)
    {
        return $this->parent->render($this->groupName($group));
    }

    /**
     * {@inheritdoc}
     *
     * @param string   $groupName
     * @param callable $renderer
     *
     * @return self
     **/
    public function renderGroupWith($groupName, callable $renderer)
    {
        $this->parent->renderGroupWith($this->groupName($groupName), $renderer);

        return $this;
    }

     /**
      * {@inheritdoc}
      *
      * @return string
      **/
     public function groupPrefix()
     {
         return $this->groupPrefix;
     }

    /**
     * {@inheritdoc}
     *
     * @param array $attributes
     *
     * @return self
     *
     * @see \Ems\Contracts\Core\Copyable
     **/
    public function replicate(array $attributes = [])
    {
        $groupPrefix = isset($attributes['groupPrefix']) ? $attributes['groupPrefix'] : '';

        return new static($this->parent, $this->namer, $this->groupName($groupPrefix));
    }

    /**
     * {@inheritdoc}
     *
     * @param string $key
     *
     * @throws \Ems\Contracts\Core\Unsupported
     *
     * @return mixed
     **/
    public function getOption($key)
    {
        return $this->parent->getOption($key);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $key
     * @param mixed  $value
     *
     * @throws \Ems\Contracts\Core\Unsupported
     *
     * @return self
     **/
    public function setOption($key, $value)
    {
        $this->parent->setOption($key, $value);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     **/
    public function supportedOptions()
    {
        return $this->parent->supportedOptions();
    }

    /**
     * {@inheritdoc}
     *
     * @param string|array $keys (optional)
     *
     * @throws \Ems\Contracts\Core\Unsupported
     *
     * @return self
     **/
    public function resetOptions($keys = null)
    {
        $this->parent->resetOptions($keys);

        return $this;
    }

    /**
     * Merged the pased group with the namespace.
     *
     * @param string $asset
     * @param string $group (optional)
     *
     * @return string
     **/
    protected function mergePassedGroup($asset, $group = null)
    {
        if (!$this->groupPrefix) {
            return $group;
        }
        $group = $group ? $group : $this->namer->guessGroup($asset);

        return $this->groupName($group);
    }

    /**
     * Return the groupName for $group. If a prefix is set, prefix it.
     *
     * @param string $group
     *
     * @return string
     **/
    protected function groupName($group)
    {
        if (!$this->groupPrefix) {
            return $group;
        }

        return "{$this->groupPrefix}.$group";
    }
}
