<?php

namespace Ems\View;

use Ems\Contracts\View\Highlight as HighlightContract;
use Ems\Contracts\View\HighlightItemProvider;
use Ems\Core\Support\CacheableTrait;
use ArrayIterator;
use RuntimeException;
use InvalidArgumentException;
use DateTime;

/**
 * Note: The Highlight caches its string output, to cache the
 * count() result you have to implement a cache in your
 * HighlightItemProvider.
 **/
class Highlight extends View implements HighlightContract
{
    use CacheableTrait;

    /**
     * @var int
     **/
    protected $limit;

    /**
     * @var int
     **/
    protected $randomCombinations;

    /**
     * @var string
     **/
    protected $method = '';

    /**
     * @var array
     **/
    protected $criterias = [];

    /**
     * @var string
     **/
    protected $template = '';

    /**
     * @var \Ems\Contracts\View\HighlightItemProvider
     **/
    protected $itemProvider;

    /**
     * @var array
     **/
    protected $result;

    /**
     * @var string
     **/
    protected $cachedString = '';

    /**
     * @var array
     **/
    protected $randomIntegers;

    /**
     * @var int
     **/
    protected $count;

    /**
     * {@inheritdoc}
     *
     * @param int $limit
     *
     * @return self
     **/
    public function limit($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param int $combinations (optional)
     *
     * @return self
     **/
    public function randomize($combinations = 5)
    {
        $this->randomCombinations = $combinations;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param string (latest|top|random)
     *
     * @return self
     **/
    public function method($method)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $criteria
     * @param array $parameters (optional)
     *
     * @return self
     **/
    public function __call($criteria, $parameters = [])
    {
        if (!$parameters) {
            $this->criterias[$criteria] = true;

            return $this;
        }

        $this->criterias[$criteria] = count($parameters) > 1 ? $parameters : $parameters[0];

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     **/
    public function criterias()
    {
        return $this->criterias;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $template (optional)
     *
     * @return self
     **/
    public function render($template = '')
    {
        $this->name = $template;

        return $this;
    }

    /**
     * Set the item provider to allow deferred loading.
     *
     * @param \Ems\Contracts\View\HighlightItemProvider $provider
     *
     * @return self
     **/
    public function setItemProvider(HighlightItemProvider $provider)
    {
        $this->itemProvider = $provider;

        return $this;
    }

    /**
     * Iterate over all items.
     *
     * @return \ArrayIterator
     **/
    public function getIterator()
    {
        return new ArrayIterator($this->getResultOnce());
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     **/
    public function mimeType()
    {
        return 'text/html';
    }

    public function cacheId()
    {
        if (!$this->randomCombinations) {
            return $this->baseCacheId();
        }

        $cacheId = [$this->baseCacheId()];

        $cacheId[] = 'random';

        $randomIntegers = $this->randomIntegers();
        sort($randomIntegers);

        $cacheId[] = implode('_', $randomIntegers);

        return implode('_', $cacheId);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     **/
    public function __toString()
    {
        try {
            return (string) $this->renderOrCache();
        } catch (\Exception $e) {
            return $this->processException($e);
        }
    }

    /**
     * Count all results.
     *
     * @return array
     **/
    public function count()
    {
        if ($this->count == null) {
            $this->count = $this->itemProvider->count($this->method, $this->criterias());
        }

        return $this->count;
    }

    protected function fillAttributes()
    {
        $this->assign('highlights', $this->getResultOnce());
    }

    /**
     * Loads the result.
     *
     * @return array
     **/
    protected function getResultOnce()
    {
        if ($this->result !== null) {
            return $this->result;
        }

        $this->result = $this->randomCombinations ? $this->getRandomizedResult()
                                            : $this->getFromProvider($this->limit);

        return $this->result;
    }

    /**
     * @return array
     **/
    protected function getRandomizedResult()
    {
        $ordered = $this->getFromProvider($this->providerLimit());

        $indexes = $this->randomIntegers();

        $result = [];

        foreach ($indexes as $index) {
            if (isset($ordered[$index])) {
                $result[] = $ordered[$index];
            }
        }

        return $result;
    }

    /**
     * @return array
     **/
    protected function getFromProvider($limit)
    {
        $items = [];

        foreach (call_user_func([$this->itemProvider, $this->method], $this->criterias(), $limit) as $item) {
            $items[] = $item;
        }

        return $items;
    }

    /**
     * @return array
     **/
    protected function randomIntegers()
    {
        if ($this->randomIntegers === null) {
            $this->randomIntegers = $this->nextRandomSeed($this->limit, $this->providerLimit());
        }

        return $this->randomIntegers;
    }

    /**
     * @return int
     **/
    protected function providerLimit()
    {
        if (!$this->randomCombinations) {
            return $this->limit;
        }
        if ($this->limit === null) {
            throw new RuntimeException('For randomized results you habe to pass a limit');
        }

        return $this->limit * $this->randomCombinations;
    }

    protected function nextRandomSeed($limit, $max)
    {
        if (!$this->_cache) {
            return $this->buildRandomIntegers($limit, $max);
        }

        $cacheId = $this->randomSeedCacheId();

        $expires = (new DateTime())->modify('+1 day');

        $freshlyCreated = false;

        if (!$combinations = $this->_cache->get($cacheId)) {
            $combinations = $this->freshRandomSeed($limit, $max, $this->randomCombinations, $expires);
            $this->_cache->put($cacheId, $combinations);
            $freshlyCreated = true;
        }

        if ($freshlyCreated) {
            return $combinations['seeds'][$combinations['current']];
        }

        //update $expires from cache
        $expires = DateTime::createFromFormat(DateTime::ATOM, $combinations['expires']);
        $next = $combinations['current'] + 1;
        $lastSeed = count($combinations['seeds']) - 1;
        $combinations['current'] = $next <= $lastSeed ? $next : 0;

        $this->_cache->until($expires)->put($cacheId, $combinations);

        return $combinations['seeds'][$combinations['current']];
    }

    protected function freshRandomSeed($limit, $max, $combinations, DateTime $expires)
    {
        $structure = [
            'seeds' => [], // The index combinations
            'current' => 0, // the last shown index
            'expires' => $expires->format(DateTime::ATOM),
        ];

        for ($i = 0, $exclude = []; $i < $combinations; ++$i) {
            $structure['seeds'][$i] = $this->buildRandomIntegers($limit, $max, $exclude);
            $exclude = array_unique(array_merge($exclude, $structure['seeds'][$i]));
        }

        return $structure;
    }

    /**
     * Create some random integers.
     *
     * @param int  $count
     * @param int  $max    (optional)
     * @param bool $unique (optional)
     *
     * @return array
     **/
    protected function buildRandomIntegers($count, $max = null, $excludes = [], $unique = true)
    {
        $max = $max ?: $count;

        // If it should not be unique do the simple task
        if (!$unique) {
            $numbers = [];
            for ($i = 0; $i < $count; ++$i) {
                $numbers[] = rand(0, $max - 1);
            }

            return $numbers;
        }

        if ($count > $max && $unique) {
            throw new InvalidArgumentException("An array with $count unique values with more items than max ($max) is impossible");
        }

        // Generate numbers until max
        $numbers = range(0, $max - 1);

        // Remove all excludes from the numbers
        foreach ($excludes as $exclude) {
            if (isset($numbers[$exclude])) {
                unset($numbers[$exclude]);
            }
        }

        // If the numbers are less than count, add the excludes back until count

        $i = 0;
        while (count($numbers) < $count) {
            $numbers[] = $excludes[$i];
            ++$i;
        }

        shuffle($numbers);

        $slice = [];

        for ($i = 0, $n = 0; $i < $count; ++$i) {
            $slice[] = array_pop($numbers);
        }

        return $slice;
    }

    /**
     * Generate the base id (without random).
     *
     * @return string
     **/
    protected function baseCacheId()
    {
        if ($this->_cacheId) {
            return $this->_cacheId;
        }

        $cacheId = [
            'highlight',
            $this->itemProvider->resourceName(),
            $this->method,
            $this->limit ? (string) $this->limit : 'X',
            $this->criterias(),
        ];

        if ($this->name) {
            $cacheId[] = $this->name;
        }

        return $this->_cache->key($cacheId);
    }

    protected function randomSeedCacheId()
    {
        return $this->baseCacheId().'_seed';
    }

    protected function renderHighlights()
    {
        $this->fillAttributes();

        return $this->renderString();
    }

    protected function renderOrCache()
    {
        if (!$this->_cache) {
            return $this->renderHighlights();
        }

        return $this->_cache->get($this, function () {
            return $this->renderHighlights();
        });
    }
}
