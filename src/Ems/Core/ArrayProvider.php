<?php
/**
 * Created by PhpStorm.
 * User: michi
 * Date: 18.11.17
 * Time: 07:17
 */

namespace Ems\Core;

use ArrayAccess;
use Ems\Contracts\Core\None;
use Ems\Contracts\Core\Provider;
use Ems\Core\Exceptions\KeyLengthException;
use Ems\Core\Exceptions\KeyNotFoundException;
use Ems\Core\Exceptions\NotImplementedException;

/**
 * Class ArrayProvider
 *
 * The array provider is a proxy to use a array (or storages) as an provider.
 * It loads all the array data into an cached array and queries that array.
 * You can add multiple storages to allow a priority list of asking
 * arrays. You could add for example config files in different
 * files, add each of the storages and then it will iterate over that
 * storages.
 *
 *
 * @package Ems\Core
 */
class ArrayProvider implements Provider, ArrayAccess
{

    /**
     * Here the query results are cached. ($provider->get('auth.guard.driver'))
     *
     * @var array
     */
    protected $queryHits = [];

    /**
     * Here the query misses are cached.
     *
     * @var array
     */
    protected $queryMisses = [];

    /**
     * @var array
     */
    protected $cachedData = [
        'default' => []
    ];

    /**
     * Here the actual added data is held.
     *
     * @var array
     */
    protected $addedData = [
        'default' => []
    ];

    /**
     * The values you set with offsetSet()
     *
     * @var array
     */
    protected $customData = [];

    /**
     * @var array
     */
    protected $prefixLengths = [];

    /**
     * @var string
     */
    protected $nsSeparator = '::';

    /**
     * Get a named object by its id.
     *
     * @param mixed $key
     * @param mixed $default (optional)
     *
     * @return mixed
     **/
    public function get($key, $default = null)
    {
        if (isset($this->queryHits[$key])) {
            return $this->queryHits[$key];
        }

        if (isset($this->queryMisses[$key])) {
            return $default;
        }

        list($namespace, $dataKey) = $this->namespaceAndKey($key);

        $prefix = $this->firstKeySegments($dataKey, $this->prefixLength($namespace, $dataKey));
        $arrayKey = $this->arrayKey($dataKey, $prefix);

        foreach ($this->dataForPrefix($namespace, $prefix) as $data) {

            if (!is_array($data)) {
                $this->queryHits[$key] = $data;
                return $data;
            }

            $result = $arrayKey ? Helper::offsetGet($data, $arrayKey) : $data;

            if ($result === null) {
                continue;
            }

            $this->queryHits[$key] = $result;
            return $result;
        }

        $this->queryMisses[$key] = true;

        return $default;

    }

    /**
     * Get a named object by its id or throw an exception if it cant be found.
     *
     * @param mixed $key
     *
     * @throws \Ems\Contracts\Core\NotFound
     *
     * @return mixed
     **/
    public function getOrFail($key)
    {
        $value = $this->get($key, new None());
        if (!$value instanceof None) {
            return $value;
        }
        throw new KeyNotFoundException("Key '$key' not found'");
    }

    /**
     * Whether a offset exists
     *
     * @param mixed $offset
     *
     * @return boolean
     *
     */
    public function offsetExists($offset)
    {
        try {
            $this->getOrFail($offset);
            return true;
        } catch (KeyNotFoundException $e) {
            return false;
        } catch (\OutOfBoundsException $e) {
            return false;
        }
    }

    /**
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     * @since 5.0.0
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetSet($offset, $value)
    {
        list($namespace, $dataKey) = $this->namespaceAndKey($offset);

        $prefix = $this->firstKeySegments($dataKey, $this->prefixLength($namespace, $dataKey));
        $arrayKey = $this->arrayKey($dataKey, $prefix);

        if (!isset($this->customData[$namespace])) {
            $this->customData[$namespace] = [];
        }

        if (!isset($this->customData[$namespace][$prefix])) {
            $this->customData[$namespace][$prefix] = [];
        }

        if ($arrayKey) {
            Helper::offsetSet($this->customData[$namespace][$prefix], $arrayKey, $value);
        } else {
            $this->customData[$namespace][$prefix] = $value;
        }

        // We have to clear the whole query cache...
        $this->queryHits = [];
        $this->queryMisses = [];

    }

    /**
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset($offset)
    {
        throw new NotImplementedException('Unsetting offets is not supported currently');
    }

    /**
     * Add new data to this provider. If there is already data for that
     * namespace it will get appended. If you want to prepend it, use
     * self::prepend().
     *
     * @param array|ArrayAccess $data
     * @param string            $namespace (default:'default')
     *
     * @return $this
     */
    public function add($data, $namespace='default')
    {
        Helper::forceArrayAccess($data);

        if (!isset($this->addedData[$namespace])) {
            $this->addedData[$namespace] = [];
        }

        $this->addedData[$namespace][] = $data;

        return $this;
    }

    /**
     * Prepend new data for $namespace. This data will be scanned first if
     * others where added before.
     *
     * @param array|ArrayAccess $data
     * @param string            $namespace (default:'default')
     *
     * @return $this
     */
    public function prepend($data, $namespace='default')
    {
        Helper::forceArrayAccess($data);

        if (!isset($this->addedData[$namespace])) {
            $this->addedData[$namespace] = [];
        }

        array_unshift($this->addedData[$namespace], $data);

        return $this;
    }

    /**
     * Clears all data of namespace $namespace
     *
     * @param string $namespace (default:'default')
     */
    public function clear($namespace='default')
    {
        $cleared = false;

        if (isset($this->addedData[$namespace])) {
            $this->addedData[$namespace] = [];
            $cleared = true;
        }

        if (isset($this->cachedData[$namespace])) {
            $this->cachedData[$namespace] = [];
            $cleared = true;
        }

        if ($cleared) {
            $this->queryHits = [];
        }
    }

    /**
     * Return the data from an added array.
     *
     * @param string $namespace
     * @param string $prefix
     *
     * @return array
     */
    protected function dataForPrefix($namespace, $prefix)
    {
        if (!isset($this->cachedData[$namespace][$prefix])) {
            $this->loadDataForPrefix($namespace, $prefix);

        }

        // If no custom data was setted using offsetSet() just return that
        if (!isset($this->customData[$namespace][$prefix])) {
            return $this->cachedData[$namespace][$prefix];
        }

        $data = $this->cachedData[$namespace][$prefix];

        array_unshift($data, $this->customData[$namespace][$prefix]);

        return $data;

    }


    /**
     * Load the data from an added array
     *
     * @param $namespace
     * @param $prefix
     * @return mixed
     */
    protected function loadDataForPrefix($namespace, $prefix)
    {
        $this->failOnMissingNamespace($namespace);

        if (!isset($this->cachedData[$namespace])) {
            $this->cachedData[$namespace] = [];
        }

        if (!isset($this->cachedData[$namespace][$prefix])) {
            $this->cachedData[$namespace][$prefix] = [];
        }

        foreach ($this->addedData[$namespace] as $data) {

            if (isset($data[$prefix])) {
                $this->cachedData[$namespace][$prefix][] = $data[$prefix];
            }
        }

        return $this->cachedData[$namespace][$prefix];
    }

    /**
     * Determine the amount of segments that should be cached. Within a normal
     * array this is always 1. But some storages are using nested directories
     * and these needs a minimum key segments.
     * This is done once and perhaps expensive, depends on the storage.
     *
     * @param string $namespace
     * @param string $key
     *
     * @return int
     */
    protected function prefixLength($namespace, $key)
    {
        if (isset($this->prefixLengths[$namespace])) {
            return $this->prefixLengths[$namespace];
        }

        $firstSegment = $this->firstKeySegments($key);

        $this->failOnMissingNamespace($namespace);

        foreach ($this->addedData[$namespace] as $data) {

            try {

                // If no exception occurs this is the right length
                isset($data[$firstSegment]);

                $this->prefixLengths[$namespace] = 1;
                return $this->prefixLengths[$namespace];

            } catch (KeyLengthException $e) {
                $this->prefixLengths[$namespace] = $e->getMinSegments();
                return $this->prefixLengths[$namespace];
            }

        }

        return 1;

    }

    /**
     * Return the namespace and key separately.
     *
     * @param $key
     *
     * @return array
     */
    protected function namespaceAndKey($key)
    {
        if ($this->hasNamespace($key)) {
            return explode($this->nsSeparator, $key, 2);
        }
        return ['default', $key];
    }

    /**
     * Return if $key does contain a namespace.
     *
     * @param string $key
     *
     * @return bool
     */
    protected function hasNamespace($key)
    {
        return (bool)strpos($key, $this->nsSeparator);
    }

    /**
     * Return the first segment(s) of a key.
     *
     * @param string $key
     * @param int    $count (default:1)
     *
     * @return string
     */
    protected function firstKeySegments($key, $count=1)
    {
        $segments = explode('.', $key);
        return implode('.', array_slice($segments, 0, $count));
    }

    /**
     * Return the array key inside a cached array.
     *
     * @param string $key
     * @param string $prefix
     *
     * @return string
     */
    protected function arrayKey($key, $prefix)
    {
        $arrayKey = substr($key, strlen($prefix)+1);
        return is_bool($arrayKey) ? '' : $arrayKey;
    }

    /**
     * @param string $namespace
     *
     * @throws \OutOfBoundsException
     */
    protected function failOnMissingNamespace($namespace)
    {
        if (!isset($this->addedData[$namespace])) {
            throw new \OutOfBoundsException("Namespace '$namespace' is unknown. (Because no data was added for it)");
        }
    }
}