<?php
/**
 *  * Created by mtils on 14.12.17 at 05:55.
 **/

namespace Ems\Model;

use function array_key_exists;
use function call_user_func;
use Ems\Contracts\Model\Exceptions\RelationNotFoundException;
use Ems\Contracts\Model\OrmCollection;
use Ems\Contracts\Model\OrmObject as OrmObjectContract;
use Ems\Contracts\Model\Relation;
use Ems\Core\Collections\StringList;
use Ems\Core\Exceptions\NotImplementedException;
use Ems\Core\Exceptions\UnConfiguredException;
use function is_callable;


abstract class OrmObject implements OrmObjectContract
{
    /**
     * Change this property to another key if your id is not named "id".
     *
     * @var string
     */
    protected $idKey = 'id';

    /**
     * @var array
     */
    protected $attributes = [];

    /**
     * @var array
     */
    protected $originalAttributes = [];

    /**
     * @var bool
     **/
    protected $loadedFromStorage = false;

    /**
     * @var callable
     */
    protected $lazyLoader;

    /**
     * @var array
     */
    protected static $relations = [];

    /**
     * OrmObject constructor.
     *
     * @param array $attributes (optional)
     * @param bool $isFromStorage (default: false)
     * @param callable $lazyLoader (optional)
     */
    public function __construct(array $attributes=[], $isFromStorage=false, callable $lazyLoader=null)
    {
        $this->lazyLoader = $lazyLoader;
        $this->init($attributes, $isFromStorage);
        $this->originalAttributes = $attributes;
        $this->attributes = $attributes;
        $this->loadedFromStorage = $isFromStorage;
    }

    /**
     * {@inheritdoc}
     *
     * @return mixed (int|string)
     **/
    public function getId()
    {
        return $this->__get($this->idKey);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     *
     * @throws NotImplementedException
     **/
    public function resourceName()
    {
        throw new NotImplementedException('Please implement resourceName method');
    }

    /**
     * @inheritdoc
     *
     * @return bool
     **/
    public function isNew()
    {
        return !$this->loadedFromStorage;
    }

    /**
     * {@inheritdoc}
     *
     * @param string|null $key (optional)
     * @param mixed $default (optional)
     *
     * @return mixed
     **/
    public function getOriginal($key = null, $default = null)
    {
        if (!$key) {
            return $this->originalAttributes;
        }

        if (array_key_exists($key, $this->originalAttributes)) {
            return $this->originalAttributes[$key];
        }

        return $default;
    }

    /**
     * {@inheritdoc}
     *
     * @param string|array $keys (optional)
     *
     * @return bool
     **/
    public function wasModified($keys = null)
    {
        $modified = $this->getModifiedData();
        $removedKeys = $this->getRemovedKeys();

        if (!$keys) {
            return (bool)$modified || (bool)$removedKeys;
        }

        $keys = is_array($keys) ? $keys : func_get_args();

        foreach ($keys as $key) {

            if (array_key_exists($key, $modified)) {
                return true;
            }

            if (in_array($key, $removedKeys)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     *
     * @param string|array $keys (optional)
     *
     * @return bool
     **/
    public function wasLoaded($keys = null)
    {
        if (!$keys) {
            return !$this->isNew();
        }

        if ($this->isNew()) {
            return false;
        }

        $keys = is_array($keys) ? $keys : func_get_args();

        foreach ($keys as $key) {
            if (!array_key_exists($key, $this->originalAttributes)) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $key
     *
     * @return bool
     */
    public function isRelation($key)
    {
        if (!isset(static::$relations[static::class])) {
            static::$relations[static::class] = static::buildRelations();
        }
        return isset(static::$relations[static::class][$key]);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $key
     *
     * @return Relation
     */
    public function getRelation($key)
    {
        if (!$this->isRelation($key)) {
            throw new RelationNotFoundException("Key $key is no relation");
        }
        return static::$relations[static::class][$key];
    }

    /**
     * Get the content of relation $key
     *
     * @param $key
     *
     * @return self|OrmCollection
     *
     * @throws UnConfiguredException
     */
    public function getRelated($key)
    {

        if (isset($this->attributes[$key])) {
            return $this->attributes[$key];
        }

        if (!$this->isRelation($key)) {
            throw new RelationNotFoundException("Key $key is no relation");
        }

        if (!is_callable($this->lazyLoader)) {
            throw new UnConfiguredException('No callable assigned to load the relations.');
        }

        $this->attributes[$key] = call_user_func($this->lazyLoader, $this, $key);

        return $this->attributes[$key];

    }

    /**
     * Check if the related object(s) of relation $key were loaded.
     *
     * @param $key
     *
     * @return bool
     */
    public function relatedLoaded($key)
    {
        return $this->isRelation($key) && isset($this->attributes[$key]);
    }

    /**
     * Return true if the passed key should be lazy loaded.
     *
     * @param string $key
     * @return bool
     */
    public function isLazyLoadKey($key)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     *
     * @param $name string
     * @return mixed
     * @link http://php.net/manual/en/language.oop5.overloading.php#language.oop5.overloading.members
     */
    public function __get($name)
    {
        if (isset($this->attributes[$name])) {
            return $this->attributes[$name];
        }

        if ($this->isRelation($name)) {
            $this->attributes[$name] = $this->getRelated($name);
            $this->originalAttributes[$name] = $this->attributes[$name];
            return $this->attributes[$name];
        }

        return null;
    }

    /**
     * {@inheritdoc}
     *
     * @param $name string
     * @param $value mixed
     * @return void
     * @link http://php.net/manual/en/language.oop5.overloading.php#language.oop5.overloading.members
     */
    public function __set($name, $value)
    {
        $this->attributes[$name] = $value;
    }

    /**
     * {@inheritdoc}
     *
     * @param $name string
     * @return bool
     * @link http://php.net/manual/en/language.oop5.overloading.php#language.oop5.overloading.members
     */
    public function __isset($name)
    {
        return array_key_exists($name, $this->attributes);
    }

    /**
     * {@inheritdoc}
     *
     * @param $name string
     * @return void
     * @link http://php.net/manual/en/language.oop5.overloading.php#language.oop5.overloading.members
     */
    public function __unset($name)
    {
        unset($this->attributes[$name]);
    }


    /**
     * {@inheritdoc}
     *
     * @return \Ems\Core\Collections\OrderedList
     **/
    public function keys()
    {
        return new StringList(array_keys($this->attributes));
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     **/
    public function toArray()
    {
        return $this->attributes;
    }

    /**
     * Reset the state to the original.
     *
     * @return self
     **/
    public function reset()
    {
        $this->attributes = $this->originalAttributes;
        return $this;
    }

    /**
     * Do some custom things here.
     *
     * @param array $attributes
     * @param bool $isFromStorage
     */
    protected function init(array &$attributes, $isFromStorage)
    {
        //
    }

    /**
     * Build your relations here
     *
     * @return array
     */
    protected static function buildRelations()
    {
        return [];
    }

    /**
     * Return all modified keys. This is useful for saving data in storages
     * which support partial updates like in sql.
     *
     * @return array
     **/
    protected function getModifiedData()
    {
        $modified = [];

        foreach ($this->attributes as $key=>$value) {

            // Every previously not existing key is modified
            if (!array_key_exists($key, $this->originalAttributes)) {
                $modified[$key] = $value;
                continue;
            }

            if ($this->valueWasModified($this->originalAttributes[$key], $this->attributes[$key])) {
                $modified[$key] = $value;
            }
        }

        return $modified;
    }

    /**
     * Return all removed keys. You could set them all to zero in storage.
     *
     * @return array
     **/
    protected function getRemovedKeys()
    {
        $removedKeys = [];

        foreach ($this->originalAttributes as $key=>$value) {

            if (!array_key_exists($key, $this->attributes)) {
                $removedKeys[] = $key;
                continue;
            }
        }

        return $removedKeys;
    }

    /**
     * Check if a value was modified
     *
     * @param mixed $originalValue
     * @param mixed $currentValue
     *
     * @return bool
     **/
    protected function valueWasModified($originalValue, $currentValue)
    {
        return $originalValue !== $currentValue;
    }

}