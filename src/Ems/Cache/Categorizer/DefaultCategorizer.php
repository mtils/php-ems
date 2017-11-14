<?php

namespace Ems\Cache\Categorizer;

use Ems\Contracts\Cache\Cacheable;
use Ems\Contracts\Cache\Categorizer;
use Ems\Contracts\Core\Identifiable;
use Ems\Contracts\Core\AppliesToResource;
use Exception;
use InvalidArgumentException;

class DefaultCategorizer implements Categorizer
{
    /**
     * @var string
     **/
    protected $defaultLifeTime = '1 day';

    /**
     * @var int
     **/
    protected $maxArrayValueLength = 32;

    /**
     * @var \Ems\Contracts\Cache\Categorizer
     **/
    protected $objectCategorizer;

    /**
     * @param \Ems\Contracts\Cache\Categorizer $objectCategorizer
     **/
    public function __construct(Categorizer $objectCategorizer)
    {
        $this->objectCategorizer = $objectCategorizer;
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $value
     *
     * @return string|null $id
     **/
    public function key($value)
    {
        if ($value instanceof Cacheable) {
            return $value->cacheId();
        }

        if (is_scalar($value)) {
            return $value;
        }

        if (is_array($value)) {
            return $this->arrayToKey($value);
        }

        if (is_object($value)) {
            return $this->objectToKey($value);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $value
     *
     * @return array|null
     **/
    public function tags($value)
    {
        if ($value instanceof Cacheable) {
            $value->cacheTags();
        }
        return null;
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $value
     *
     * @return \DateTime|null
     **/
    public function lifetime($value)
    {
        if ($value instanceof Cacheable) {
            return $value->lifetime();
        }
        return null;
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $value
     *
     * @return array
     */
    public function pruneAfterForget($value)
    {
        return [];
    }

    /**
     * Turns an array into a key.
     *
     * @param array $array
     *
     * @return string
     **/
    protected function arrayToKey(array $array)
    {
        if ($this->isSequential($array)) {
            return $this->sequentialArrayToKey($array);
        }

        $parts = [];

        $keys = array_keys($array);
        sort($keys);

        foreach ($keys as $key) {
            $parts[] = $key;
            $parts[] = $this->castArrayValue($array[$key]);
        }

        return implode('_', $parts);
    }

    /**
     * Turns an strictly sequential array into a key.
     *
     * @param array $array
     *
     * @return string
     **/
    protected function sequentialArrayToKey(array $array)
    {

        if ($this->isObjectAndId($array)) {
            return get_class($array[0]) . '-' . $array[1];
        }

        $parts = [];

        foreach ($array as $value) {
            $parts[] = $this->castArrayValue($this->key($value));
        }

        return implode('_', $parts);
    }

    /**
     * Turns an object into a key.
     *
     * @param object $object
     *
     * @return string
     **/
    protected function objectToKey($object)
    {
        $typeName = $object instanceof AppliesToResource ? $object->resourceName() : get_class($object);

        if (!$object instanceof Identifiable) {
            throw new InvalidArgumentException('Cannot generate cache id of unknown class '.get_class($object));
        }

        return $typeName.'_'.$object->getId();
    }

    /**
     * Casts an array value. Is used different to avoid endless recursion.
     *
     * @param mixed $value
     *
     * @return string
     **/
    protected function castArrayValue($value)
    {
        if (!is_object($value)) {
            return $this->key($value);
        }

        try {
            if ($key = $this->objectCategorizer->key($value)) {
                return $this->hashIfToLong($key);
            }
        } catch (Exception $e) {
        }

        return $this->hashIfToLong($this->objectToKey($value));
    }

    /**
     * Hashes a key if the key is too long.
     *
     * @param string $string
     *
     * @return string
     **/
    protected function hashIfToLong($string)
    {
        if (strlen($string) > $this->maxArrayValueLength) {
            return md5($string);
        }
        return $string;
    }

    /**
     * This is for a special syntax to retrieve identifiable objects before
     * having them.
     * Just pass an array of [$object, $id].
     * Because of not autloading something without needing it you have to pass
     * an (empty) object and not a class.
     *
     * @param array $array
     *
     * @return bool
     */
    protected function isObjectAndId(array $array)
    {
        if (count($array) === 2 && isset($array[0]) && is_object($array[0])) {
            return true;
        }

        return false;
    }

    /**
     * Strictly check if an array is sequential.
     *
     * @param array
     *
     * @return bool
     **/
    protected function isSequential(array $array)
    {
        if ($array === []) {
            return true;
        }

        return array_keys($array) === range(0, count($array) - 1);
    }
}
