<?php


namespace Ems\Assets;


use Ems\Contracts\Assets\BuildConfig as BuildConfigContract;
use Ems\Contracts\Assets\Collection as CollectionContract;
use Ems\Contracts\Model\Repository;


class BuildConfig implements BuildConfigContract
{

    /**
     * @var \Ems\Contracts\Model\Repository
     **/
    protected $repository;

    /**
     * @var string
     **/
    protected $group;

    /**
     * @var string
     **/
    protected $target;

    /**
     * @var \Ems\Contracts\Assets\Collection
     **/
    protected $collection;

    /**
     * @var array
     **/
    protected $parserNames = [];

    /**
     * @var array
     **/
    protected $parserOptions = [];

    /**
     * @var array
     **/
    protected $managerOptions = [];

    /**
     * @var array
     **/
     protected $compilerOptions = [];

    /**
     * @var bool
     **/
    protected $lazyLoad = false;

    /**
     * @var array
     **/
    protected $lazyLoadAttributes = [];

    /**
     * @var bool
     **/
    protected $lazyLoaded = false;

    /**
     * @see \Ems\Contracts\Core\Identifiable
     * @return string
     **/
    public function getId()
    {
        return $this->group;
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
     * @return string
     **/
    public function target()
    {
        $this->fillIfNotFilled();
        return $this->target;
    }

    /**
     * @param string $group
     * @return self
     **/
    public function setTarget($target)
    {
        $this->target = $target;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return \Ems\Contracts\Assets\Collection
     **/
    public function collection()
    {
        $this->fillIfNotFilled();
        return $this->collection;
    }

    /**
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
     * @return array
     **/
    public function parserNames()
    {
        $this->fillIfNotFilled();
        return $this->parserNames;
    }

    /**
     * @param array|string $names
     * @return self
     **/
    public function setParserNames($names)
    {
        $this->parserNames = (array)$names;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $parserName (optional)
     * @return array
     **/
    public function parserOptions($parserName=null)
    {
        $this->fillIfNotFilled();
        if (!$parserName) {
            return $this->parserOptions;
        }
        if (isset($this->parserOptions[$parserName])) {
            return $this->parserOptions[$parserName];
        }
        return [];
    }

    /**
     * Set a parser option
     *
     * @param string $parserName
     * @param string|array $key
     * @param mixed $value
     * @return self
     **/
    public function setParserOption($parserName, $key, $value=null)
    {
        if (!isset($this->parserOptions[$parserName])) {
            $this->parserOptions[$parserName] = [];
        }

        if (!is_array($key)) {
            $this->parserOptions[$parserName][$key] = $value;
            return $this;
        }

        foreach ($key as $name=>$value) {
            $this->setParserOption($parserName, $name, $value);
        }

        return $this;
    }

    /**
     * Clears all parser options for $parserName
     *
     * @param string $parserName
     * @return self
     **/
    public function resetParserOptions($parserName)
    {
        if (isset($this->parserOptions[$parserName])) {
            $this->parserOptions[$parserName] = [];
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param string $parserName (optional)
     * @return array
     **/
    public function managerOptions()
    {
        $this->fillIfNotFilled();
        return $this->managerOptions;
    }

    /**
     * Set the options for the asset manager
     *
     * @param array $options
     * @return self
     **/
    public function setManagerOptions(array $options)
    {
        $this->managerOptions = $options;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     **/
    public function compilerOptions()
    {
        $this->fillIfNotFilled();
        return $this->compilerOptions;
    }

    /**
     * Set the options for the compiler
     *
     * @param array $options
     * @return self
     **/
    public function setCompilerOptions(array $options)
    {
        $this->compilerOptions = $options;
        return $this;
    }

    /**
     * Lazyload the attributes of this BuildConfig by passing some attributes
     * and let it later be filled by the passed repository
     * This is typehinted against the core repository because it only needs the
     * fill method
     *
     * @param \Ems\Contracts\Model\Repository $repository
     * @param array $attributes
     * @return self
     **/
    public function lazyLoadBy(Repository $repository, array $attributes)
    {
        $this->repository = $repository;
        $this->lazyLoadAttributes = $attributes;
        $this->lazyLoad = true;
        return $this;
    }

    /**
     * Fills the config by the repository once
     **/
    protected function fillIfNotFilled()
    {
        if (!$this->lazyLoad || $this->lazyLoaded || !$this->repository) {
            return;
        }

        $this->repository->fill($this, $this->lazyLoadAttributes);

        $this->lazyLoaded = true;
    }

}
