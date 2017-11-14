<?php

namespace Ems\Assets;

use Ems\Contracts\Assets\Asset as AssetContract;
use Ems\Contracts\Assets\BuildConfigRepository as RepositoryContract;
use Ems\Contracts\Assets\BuildConfig as BuildConfigContract;
use Ems\Contracts\Assets\Collection as CollectionContract;
use Ems\Contracts\Core\Filesystem;
use Ems\Contracts\Assets\Registry as RegistryContract;
use UnderflowException;
use Ems\Core\Exceptions\ResourceNotFoundException;
use Ems\Core\Exceptions\DetectionFailedException;
use InvalidArgumentException;
use Ems\Contracts\Core\Identifiable;

class BuildConfigRepository implements RepositoryContract
{
    /**
     * @var \Ems\Contracts\Assets\Registry
     **/
    protected $registry;

    /**
     * @var \Ems\Contrats\Core\Filesystem
     **/
    protected $files;

    /**
     * @var array
     **/
    protected $buildConfigs = [];

    /**
     * @var bool
     **/
    protected $filledByCallables = false;

    /**
     * @var array
     **/
    protected $fillers = [];

    /**
     * @param \Ems\Contracts\Assets\Registry $registry
     * @param \Ems\Contrats\Core\Filesystem  $files
     **/
    public function __construct(RegistryContract $registry, Filesystem $files)
    {
        $this->registry = $registry;
        $this->files = $files;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     **/
    public function has($group)
    {
        $this->autoFillIfNotDone();

        return isset($this->buildConfigs[$group]);
    }

    /**
     * {@inheritdoc}
     *
     * @param string|array $group   (optional)
     * @param mixed        $default (optional)
     *
     * @return \Ems\Contracts\Assets\BuildConfig
     **/
    public function get($group, $default = null)
    {
        $this->autoFillIfNotDone();
        if (isset($this->buildConfigs[$group])) {
            return $this->buildConfigs[$group];
        }

        return $default;
    }

    /**
     * {@inheritdoc}
     *
     * @param string|array $group (optional)
     *
     * @return \Ems\Contracts\Assets\BuildConfig
     **/
    public function getOrFail($group)
    {
        if ($config = $this->get($group)) {
            return $config;
        }

        throw new ResourceNotFoundException("No config for group $group assigned");
    }

    /**
     * {@inheritdoc}
     *
     * @return \Ems\Contracts\Assets\BuildConfig
     **/
    public function make(array $attributes = [])
    {
        $config = new BuildConfig();

        $this->fill($config, $attributes);

        return $config;
    }

    /**
     * {@inheritdoc}
     *
     * Use this method to lazyload the build configs. This is better because
     * usually the repository will be asked while rendering the page if there
     * is a built version of an asset. So using store does not trigger the
     * usually used (more expensive) fill() method
     *
     * @param array $attributes
     *
     * @return \Ems\Contracts\Core\Identifiable The created resource
     **/
    public function store(array $attributes)
    {
        if (!isset($attributes['group'])) {
            throw new OutOfBoundsException('The config needs a group value to add it');
        }

        $config = (new BuildConfig())->setGroup($attributes['group'])
                                   ->lazyLoadBy($this, $attributes);

        $this->buildConfigs[$attributes['group']] = $config;

        return $config;
    }

    /**
     * Fill the model with attributes $attributes.
     *
     * @param \Ems\Contracts\Core\Identifiable $config
     * @param array                            $attributes
     *
     * @return bool if attributes where changed after filling
     **/
    public function fill(Identifiable $config, array $attributes)
    {
        if (!$attributes) {
            return false;
        }

        foreach ($attributes as $key => $value) {
            if (in_array($key, ['collection', 'assets', 'files'])) {
                $this->addCollection($config, $value, $attributes);
                continue;
            }
            $this->addToConfig($config, $key, $value);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @param \Ems\Contracts\Core\Identifiable $config
     * @param array                            $newAttributes
     *
     * @return bool true if it was actually saved, false if not. Look above!
     **/
    public function update(Identifiable $config, array $newAttributes)
    {
        $this->fill($config, $newAttributes);
        $this->save($config);

        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @param \Ems\Contracts\Assets\BuildConfig $config
     *
     * @return self
     **/
    public function save(Identifiable $config)
    {
        $group = $this->groupOfConfig($config);
        $this->buildConfigs[$group] = $config;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param \Ems\Contracts\Core\Identifiable $model
     *
     * @return bool
     **/
    public function delete(Identifiable $model)
    {
        // Trigger throw
        $group = $this->groupOfConfig($config);

        if (isset($this->buildConfigs[$group])) {
            unset($this->buildConfigs[$group]);

            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     **/
    public function groups()
    {
        $this->autoFillIfNotDone();

        return array_keys($this->buildConfigs);
    }

    /**
     * {@inheritdoc}
     *
     * @param \Ems\Contracts\Assets\BuildConfig $config
     *
     * @return bool
     **/
    public function compiledFileExists(BuildConfigContract $config)
    {
        $absolutePath = $this->registry->to($config->group())
                                       ->absolute($config->target());

        return $this->files->exists($absolutePath);
    }

    /**
     * {@inheritdoc}
     *
     * @param callable
     *
     * @return self
     */
    public function fillRepositoryBy(callable $filler)
    {
        $this->fillers[] = $filler;

        return $this;
    }

    protected function addToConfig(BuildConfig $config, $key, $value)
    {
        switch ($key) {
            case 'group':
                return $config->setGroup($value);
            case 'target':
                return $config->setTarget($value);
            case 'parsers':
            case 'parserNames':
                return $config->setParserNames($value);
            case 'parserOptions':
                return $this->addParserOptions($config, $value);
            case 'managerOptions':
                return $config->setManagerOptions($value);
            case 'compilerOptions':
                return $config->setCompilerOptions($value);
            case 'collection':
            case 'assets':
                return $this->addCollection($config, $value);
        }

        throw new DetectionFailedException("Unknown config key $key");
    }

    protected function addParserOptions(BuildConfig $config, $options)
    {
        foreach ($options as $parserName => $options) {
            $config->setParserOption($parserName, $options);
        }
    }

    protected function addCollection(BuildConfig $config, $items, array $all)
    {
        if ($items instanceof CollectionContract) {
            return $config->setCollection($items);
        }

        if (!is_array($items)) {
            throw new InvalidArgumentException('Unknown collection type, only Collection and array is supported');
        }

        if (!isset($all['group'])) {
            throw new InvalidArgumentException('If an array as collection is passed you need to pass a group');
        }

        $group = $all['group'];

        $collection = (new Collection())->setGroup($group);

        foreach ($items as $item) {
            if ($item instanceof AssetContract) {
                $collection->append($item);
                continue;
            }

            $collection->append($this->registry->newAsset($item, $group));
        }

        $config->setCollection($collection);

        return null;
    }

    protected function groupOfConfig(BuildConfigContract $config)
    {
        if (!$group = $config->group()) {
            throw new UnderflowException('Assign a group to the config before adding it to the builder');
        }

        return $group;
    }

    protected function autoFillIfNotDone()
    {
        if ($this->filledByCallables) {
            return;
        }

        // Before fill to avoid recursion
        $this->filledByCallables = true;

        foreach ($this->fillers as $filler) {
            $filler($this);
        }
    }
}
