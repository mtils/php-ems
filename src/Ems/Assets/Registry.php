<?php

namespace Ems\Assets;

use Ems\Contracts\Assets\Registry as RegistryContract;
use Ems\Contracts\Assets\NameAnalyser;
use Ems\Contracts\Assets\Asset as AssetContract;
use Ems\Contracts\Core\PathFinder;
use Ems\Core\PathFinderProxy;
use BadMethodCallException;
use RuntimeException;
use OutOfBoundsException;
use ArrayIterator;

class Registry extends PathFinderProxy implements RegistryContract
{
    /**
     * @var \Ems\Contracts\Assets\NameAnalyser
     **/
    protected $namer;

    /**
     * @var array
     **/
    protected $imports = [];

    /**
     * @var array
     **/
    protected $prepend = [];

    /**
     * @var array
     **/
    protected $befores = [];

    /**
     * @var array
     **/
    protected $afters = [];

    /**
     * @var array
     **/
    protected $append = [];

    /**
     * @var array
     **/
    protected $addedAssets = [];

    /**
     * @var array
     **/
    protected $customHandlers = [];

    /**
     * @var array
     **/
    protected $lastTouchedAssets = [];

    /**
     * @var string
     **/
    protected $lastTouchedGroup = '';

    /**
     * @var string
     **/
    protected $skipHandler = '';

    /**
     * @param \Ems\Contracts\Assets\NameAnalyser $namer
     * @param \Ems\Contracts\Core\PathFinder     $pathFinder
     * @param string                             $assetNamespace (optional)
     **/
    public function __construct(NameAnalyser $namer, PathFinder $pathFinder, $assetNamespace = 'assets')
    {
        parent::__construct($pathFinder, $assetNamespace);
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
        $assets = $this->isNumericArray($asset) ? $asset : [$asset];

        $assetNames = [];

        foreach ($assets as $asset) {
            list($name, $attributes) = $this->toNameAndAttributes($asset);

            // First iteration only
            if (!$assetNames) {
                $group = $this->detectGroup($name, $group);
            }

            $assetNames[] = $name;

            $assertObject = $this->addImportOrInline($name, $group);

            if ($attributes) {
                $assertObject->setAttributes($attributes);
            }
        }

        $this->lastTouchedGroup = $group;
        $this->lastTouchedAssets = $assetNames;

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
        $group = $this->detectGroup($asset, $group);
        $this->lastTouchedAssets = [$asset];
        $this->lastTouchedGroup = $group;

        $this->addImportOrInline($asset, $group, $content);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * Performs lazyloading of paths because this can be expensive
     *
     * @param string $asset
     * @param string $group   (optional)
     * @param string $content (optional) (make it a inline asset)
     *
     * @return \Ems\Contracts\Assets\Asset
     **/
    public function newAsset($name, $group = null, $content = '')
    {
        $group = $this->detectGroup($name, $group);

        return (new Asset())
                ->setName($name)
                ->setGroup($group)
                ->setContent($content)
                ->setMimeType($this->namer->guessMimeType($name, $group))
                ->lazyLoadBy($this);
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     **/
    public function groups()
    {
        return array_keys($this->imports);
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
        $this->customHandlers[$asset] = $handler;

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
        $movedAssets = $this->lastTouchedAssetsOrFail();

        if ($asset === null) {
            $this->moveAssetsToAppendings($this->lastTouchedGroup, $movedAssets);

            return $this;
        }

        $positionId = $this->positionId($asset);

        if (!isset($this->afters[$positionId])) {
            $this->afters[$positionId] = [];
        }

        $this->moveAssetsToPassedArray($this->afters[$positionId], $movedAssets);

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
        $movedAssets = $this->lastTouchedAssetsOrFail();

        if ($asset === null) {
            $this->moveAssetsToPrependings($this->lastTouchedGroup, $movedAssets);

            return $this;
        }

        $positionId = $this->positionId($asset);

        if (!isset($this->befores[$positionId])) {
            $this->befores[$positionId] = [];
        }

        $this->moveAssetsToPassedArray($this->befores[$positionId], $movedAssets);

        return $this;
    }

    /**
     * The total amount of assets.
     *
     * @return int
     **/
    public function count()
    {
        return count($this->addedAssets);
    }

    /**
     * Return if a group named $offset exists.
     *
     * @param string $offset
     *
     * @return bool
     **/
    public function offsetExists($offset)
    {
        return isset($this->imports[$offset]);
    }

    /**
     * Return all files of group $offset.
     *
     * @param string $offset
     *
     * @return array
     **/
    public function offsetGet($offset)
    {
        if (isset($this->imports[$offset])) {
            return $this->sortedAssets($offset);
        }
        throw new OutOfBoundsException("Group $offset not found");
    }

    /**
     * Not allowed.
     *
     * @param string $offset
     * @param mixed  $value
     *
     * @return void
     *
     * @throws \BadMethodCallException
     **/
    public function offsetSet($offset, $value)
    {
        throw new BadMethodCallException('Cannot set values on '.__CLASS__);
    }

    /**
     * Not allowed.
     *
     * @param string $offset
     *
     * @return void
     *
     * @throws \BadMethodCallException
     **/
    public function offsetUnset($offset)
    {
        throw new BadMethodCallException('Cannot unset values on '.__CLASS__);
    }

    /**
     * Iterate over the items by $group=>$assets.
     *
     * @return ArrayIterator
     **/
    public function getIterator()
    {
        $array = [];
        foreach (array_keys($this->imports) as $group) {
            $array[$group] = $this->offsetGet($group);
        }

        return new ArrayIterator($array);
    }

    /**
     * Fill the passed asset with the missing attributes. Handy for Asset::lazyLoadBy.
     *
     * @param \Ems\Assets\Asset
     **/
    public function __invoke(Asset $asset)
    {
        $name = $asset->name();
        $group = $asset->group();
        $mapper = $this->to($group);

        $asset->setMimeType($this->namer->guessMimeType($name, $group))
              ->setUri($mapper->url($name))
              ->setPath($mapper->absolute($name));
    }

    protected function toNameAndAttributes($asset)
    {
        if (!is_array($asset)) {
            return [$asset, []];
        }

        $name = $asset['name'];
        unset($asset['name']);

        return [$name, $asset];
    }

    protected function newCollection($group)
    {
        return (new Collection())->setGroup($group);
    }

    protected function isAlreadyAdded($asset, $group)
    {
        return isset($this->addedAssets["$group|$asset"]);
    }

    protected function markAsAdded($asset, $group)
    {
        $this->addedAssets["group|$asset"] = true;
    }

    protected function addImportOrInline($asset, $group, $content = '')
    {
        if ($this->isAlreadyAdded($asset, $group)) {
            return $this;
        }

        if ($this->callHandlerIfAssigned($group, $asset)) {
            return $this;
        }

        return $this->addToImports($group, $asset, $content);
    }

    protected function addToImports($group, $asset, $content = '')
    {
        if (!isset($this->imports[$group])) {
            $this->imports[$group] = [];
        }

        $assetObject = $this->newAsset($asset, $group, $content);

        $this->imports[$group][] = $assetObject;

        $this->markAsAdded($asset, $group);

        return $assetObject;
    }

    /**
     * Call a custom handler if one was assigned. Return true
     * if a custom handler was found (and called), otherwise return false.
     *
     * @param string $group
     * @param string $asset
     *
     * @return bool
     **/
    protected function callHandlerIfAssigned($group, $asset)
    {
        if (!isset($this->customHandlers[$asset]) || $this->skipHandler == $asset) {
            return false;
        }

        $this->skipHandler = $asset;

        try {
            call_user_func($this->customHandlers[$asset], $this, $asset, $group);
        } finally {
            $this->skipHandler = '';
        }

        return true;
    }

    protected function sortedAssets($group)
    {
        $collection = $this->newCollection($group);

        $prepend = isset($this->prepend[$group]) ? $this->prepend[$group] : [];

        foreach ($prepend as $asset) {
            $this->addSorted($collection, $asset);
        }

        foreach ($this->imports[$group] as $asset) {
            $this->addSorted($collection, $asset);
        }

        if (!isset($this->append[$group])) {
            return $collection;
        }

        foreach ($this->append[$group] as $asset) {
            $this->addSorted($collection, $asset);
        }

        return $collection;
    }

    protected function addSorted(Collection $collection, AssetContract $asset)
    {
        $name = $asset->name();
        $positionId = $this->positionId($name, $asset->group());

        $insertBefore = isset($this->befores[$positionId]) ? $this->befores[$positionId] : [];

        foreach ($insertBefore as $before) {
            $collection->append($before);
        }

        // Not found or not before
        $collection->append($asset);

        if (!isset($this->afters[$positionId])) {
            return;
        }

        foreach ($this->afters[$positionId] as $afterAsset) {
            $this->addSorted($collection, $afterAsset);
        }
    }

    protected function moveAssetsToAppendings($group, array $assetNames)
    {
        if (!isset($this->append[$group])) {
            $this->append[$group] = [];
        }

        $this->moveAssetsToPassedArray($this->append[$group], $assetNames);
    }

    protected function moveAssetsToPrependings($group, array $assetNames)
    {
        if (!isset($this->prepend[$group])) {
            $this->prepend[$group] = [];
        }

        $this->moveAssetsToPassedArray($this->prepend[$group], $assetNames);
    }

    protected function moveAssetsToPassedArray(array &$array, array $assetNames)
    {
        foreach ($assetNames as $sortedAsset) {
            $array[] = $this->popFromImports($sortedAsset);
        }
    }

    protected function popFromImports($assetName)
    {
        $result = null;
        $group = $this->lastTouchedGroup;

        $foundIndex = null;
        foreach ($this->imports[$group] as $i => $asset) {
            if ($asset->name() == $assetName) {
                $foundIndex = $i;
                break;
            }
        }

        if ($foundIndex === null) {
            throw new OutOfBoundsException("Asset $assetName not found in group $group");
        }

        $asset = $this->imports[$group][$foundIndex];

        unset($this->imports[$group][$foundIndex]);

        $this->imports[$group] = array_values($this->imports[$group]);

        return $asset;
    }

    protected function detectGroup($asset, $group)
    {
        return $group ? $group : $this->namer->guessGroup($asset);
    }

    protected function lastTouchedAssetsOrFail()
    {
        if (!$this->lastTouchedAssets) {
            throw new RuntimeException('Last touched assets not found');
        }

        return $this->lastTouchedAssets;
    }

    protected function positionId($asset, $group = null)
    {
        $group = $group ?: $this->lastTouchedGroup;
        if (!$group) {
            throw new RuntimeException('Last touched group not found and group not passed');
        }

        return $group.'|'.$asset;
    }

    /**
     * Superficial check if an array is numeric.
     *
     * @param mixed $value
     *
     * @return bool
     **/
    protected function isNumericArray($value)
    {
        return is_array($value) && isset($value[0]);
    }
}
