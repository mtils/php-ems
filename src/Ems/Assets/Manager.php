<?php

namespace Ems\Assets;

use Ems\Contracts\Assets\Manager as ManagerContract;
use Ems\Contracts\Assets\Registry as RegistryContract;
use Ems\Contracts\Assets\NameAnalyser;
use Ems\Contracts\Core\Renderer;
use Ems\Contracts\Assets\BuildConfigRepository as BuildRepository;
use Ems\Contracts\Assets\BuildConfig as BuildConfigContract;
use Ems\Core\ConfigurableTrait;

class Manager implements ManagerContract
{
    use ConfigurableTrait;

    /**
     * @var \Ems\Contracts\Assets\Registry
     **/
    protected $registry;

    /**
     * @var Ems\Contracts\Core\Renderer
     **/
    protected $renderer;

    /**
     * This is only needed for replicate().
     *
     * @var \Ems\Contracts\Assets\NameAnalyser
     **/
    protected $namer;

    /**
     * @var Ems\Contracts\Assets\BuildConfigRepository
     **/
    protected $buildRepository;

    /**
     * @var array
     **/
    protected $groupRenderers = [];

    /**
     * @var bool
     **/
    protected $checkBuilds = true;

    /**
     * @var array
     **/
    protected $defaultOptions = [
        self::MERGE_UNCOMPILED_FILES => true,
        self::CHECK_COMPILED_FILE_EXISTS => true,
    ];

    /**
     * @param \Ems\Contracts\Assets\Registry     $registry
     * @param \Ems\Contracts\Core\Renderer       $renderer
     * @param \Ems\Contracts\Assets\NameAnalyser $namer
     **/
    public function __construct(RegistryContract $registry, Renderer $renderer, NameAnalyser $namer)
    {
        $this->registry = $registry;
        $this->renderer = $renderer;
        $this->namer = $namer;
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
        $this->registry->import($asset, $group);

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
        $this->registry->inline($asset, $content, $group);

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
        return $this->registry->newAsset($asset, $group, $content)
                              ->setRenderer($this->renderer);
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
        $this->registry->on($asset, $handler);

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
        $this->registry->after($asset);

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
        $this->registry->before($asset);

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
        if ($this->hasGroupRenderer($group)) {
            return $this->callCustomRenderer($group);
        }

        if ($buildConfig = $this->getBuildConfig($group)) {
            return $this->renderWithCompiled($buildConfig, $group);
        }

        return $this->registry[$group]->setRenderer($this->renderer);
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
        $this->groupRenderers[$groupName] = $renderer;

        return $this;
    }

     /**
      * {@inheritdoc}
      *
      * @return string
      **/
     public function groupPrefix()
     {
         return '';
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

        return new ManagerProxy($this, $this->namer, $groupPrefix);
    }

    /**
     * Return the assigned BuildConfigRepository.
     *
     * @return Ems\Contracts\Assets\BuildConfigRepository $repository
     **/
    public function getBuildConfigRepository()
    {
        return $this->buildRepository;
    }

    /**
     * Set a BuildConfigRepository to skip renderering of compiled
     * assets and instead render the compiled ones.
     *
     * @param Ems\Contracts\Assets\BuildConfigRepository $repository
     *
     * @return self
     **/
    public function setBuildConfigRepository(BuildRepository $repository)
    {
        $this->buildRepository = $repository;

        return $this;
    }

    /**
     * Bypass any build configurations, useful for developing. If
     * you pass true (or nothing) the manager will not look for compiled
     * builds or files and always return the plain collection.
     *
     * @param bool (optional)
     *
     * @return self
     **/
    public function bypassBuilds($check = true)
    {
        $this->checkBuilds = !$check;

        return $this;
    }

    /**
     * Find out if a custom renderer for $group was assigned.
     *
     * @param string
     *
     * @return bool
     **/
    protected function hasGroupRenderer($group)
    {
        return isset($this->groupRenderers[$group]);
    }

    /**
     * Call the custom Renderer.
     *
     * @param string
     *
     * @return \Ems\Contracts\Assets\Collection
     **/
    protected function callCustomRenderer($group)
    {
        return call_user_func($this->groupRenderers[$group], $group, $this->registry);
    }

    /**
     * Return the buildconfig for $group if a repo is assigned and
     * has one for $group.
     *
     * @param string $group
     *
     * @return \Ems\Contracts\Assets\BuildConfig
     **/
    protected function getBuildConfig($group)
    {
        if (!$this->buildRepository || !$this->checkBuilds) {
            return;
        }

        if (!$this->buildRepository->has($group)) {
            return;
        }

        $config = $this->buildRepository->getOrFail($group);

        $config->target();

        if (!$this->mergeOption(self::CHECK_COMPILED_FILE_EXISTS, $config->managerOptions())) {
            return $config;
        }

        if (!$this->buildRepository->compiledFileExists($config)) {
            return;
        }

        return $config;
    }

    /**
     * Renders the passed $group and merges it with compiled files.
     *
     * @param \Ems\Contracts\Assets\BuildConfig $config
     * @param string                            $group
     *
     * @return \Ems\Contracts\Assets\Collection
     **/
    protected function renderWithCompiled(BuildConfigContract $config, $group)
    {
        if (!$this->mergeOption(self::MERGE_UNCOMPILED_FILES, $config->managerOptions())) {
            return $config->collection()->setRenderer($this->renderer);
        }

        $compiledAsset = $this->registry->newAsset($config->target(), $group)
                                        ->setCompiled(true)
                                        ->setCollection($config->collection());

        return $this->mergeCollections($compiledAsset, $this->registry[$group])
                    ->setRenderer($this->renderer);
    }

    /**
     * Merges the passed collection with the compiled in $compiledAsset.
     *
     * @param \Ems\Contracts\Assets\BuildConfig $buildConfig
     * @param string                            $group
     *
     * @return \Ems\Contracts\Assets\Collection
     **/
    protected function mergeCollections(Asset $compiledAsset, Collection $passedCollection)
    {
        $compiledNames = [];

        $mergedCollection = (new Collection())->setGroup($compiledAsset->group());

        foreach ($compiledAsset->collection() as $asset) {
            $compiledNames[$asset->name()] = $asset;
        }

        $compiledAssetAdded = false;

        foreach ($passedCollection as $asset) {

            // asset is not in compiled asset
            if (!isset($compiledNames[$asset->name()])) {
                $mergedCollection->append($asset);
                continue;
            }

            if ($compiledAssetAdded) {
                continue;
            }

            $mergedCollection->append($compiledAsset);

            $compiledAssetAdded = true;
        }

        return $mergedCollection;
    }
}
