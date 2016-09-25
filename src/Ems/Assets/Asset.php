<?php


namespace Ems\Assets;


use Ems\Contracts\Assets\Asset as AssetContract;
use Ems\Contracts\Assets\Collection as CollectionContract;
use Ems\Core\Support\RenderableTrait;

class Asset implements AssetContract
{

    use RenderableTrait;

    /**
     *  @var string
     **/
    protected $name;

    /**
     *  @var string
     **/
    protected $mimeType;

    /**
     *  @var string
     **/
    protected $group;

    /**
     * @var bool
     **/
    protected $compiled = false;

    /**
     * @var \Ems\Contracts\Assets\Collection
     **/
    protected $collection;

    /**
     * @var string
     **/
    protected $uri;

    /**
     * @var string
     **/
    protected $path;

    /**
     * @var string
     **/
    protected $content = '';

    /**
     * @var bool
     **/
    protected $binary;

    /**
     * @var array
     **/
    protected $attributes = [];

    /**
     * @var bool
     **/
    protected $shouldLazyLoad = false;

    /**
     * @var callable
     **/
    protected $lazyLoader;


    /**
     * {@inheritdoc}
     *
     * @return string
     **/
    public function name()
    {
        return $this->name;
    }

    /**
     * Set the name of this asset
     *
     * @param string $name
     * @return self
     **/
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     **/
    public function mimeType()
    {
        return $this->mimeType;
    }

    /**
     * Set the mimetype
     *
     * @param string $type
     * @return self
     **/
    public function setMimeType($type)
    {
        $this->mimeType = $type;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     **/
    public function group()
    {
        return $this->group;
    }

    /**
     * Set the group
     *
     * @param string $group
     * @return self
     **/
    public function setGroup($group)
    {
        $this->group = $group;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     **/
    public function isCompiled()
    {
        return $this->compiled;
    }

    /**
     * Set if this asset was compiled
     *
     * @param bool $compiled
     * @return self
     **/
    public function setCompiled($compiled)
    {
        $this->compiled = $compiled;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return \Ems\Contracts\Assets\Collection
     **/
    public function collection()
    {
        if (!$this->collection) {
            $this->collection = new Collection;
        }
        return $this->collection;
    }

    /**
     * Set the collection of sub assets (if exists)
     *
     * @param \Ems\Contracts\Assets\Collection $collection
     * @return self
     **/
    public function setCollection(CollectionContract $collection)
    {
        $this->collection = $collection;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     **/
    public function uri()
    {
        $this->lazyLoadIfNeeded();
        return $this->uri;
    }

    /**
     * Set the original asset name of this asset
     *
     * @param string $uri
     * @return self
     **/
    public function setUri($uri)
    {
        $this->uri = $uri;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     **/
    public function path()
    {
        $this->lazyLoadIfNeeded();
        return $this->path;
    }

    /**
     * Set the absolute path of this asset
     *
     * @param $path
     * @return self
     **/
    public function setPath($path)
    {
        $this->path = $path;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     **/
    public function isInline()
    {
        return (bool)$this->content;
    }

    /**
     * Return the content (if inline)
     *
     * @return string
     **/
    public function content()
    {
        return $this->content;
    }

    /**
     * Set the content of this asset (for inline assets)
     *
     * @param string $content
     * @return self
     **/
    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     **/
    public function isBinary()
    {
        return $this->binary;
    }

    /**
     * Set if this asset is binary
     *
     * @param bool $binary
     * @return self
     **/
    public function setBinary($binary)
    {
        $this->binary = $binary;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     **/
    public function attributes()
    {
        return $this->attributes;
    }

    /**
     * Set the html attributes
     *
     * @param array $attributes
     * @return self
     **/
    public function setAttributes(array $attributes)
    {
        $this->attributes = $attributes;
        return $this;
    }

    /**
     * Assign a loader which will lazyload the properties
     *
     * @param callable $loader
     * @return self
     **/
    public function lazyLoadBy(callable $loader)
    {
        $this->lazyLoader = $loader;
        $this->shouldLazyLoad = true;
        return $this;
    }

    protected function lazyLoadIfNeeded()
    {
        if (!$this->shouldLazyLoad) {
            return;
        }

        $this->shouldLazyLoad = false;
        call_user_func($this->lazyLoader, $this);
    }
}
