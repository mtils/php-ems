<?php
/**
 *  * Created by mtils on 04.06.19 at 13:45.
 **/

namespace Ems\XType\Support;

use function get_class;

/**
 * Use this trait to make your Model SelfExplanatory
 *
 * @see \Ems\Contracts\XType\SelfExplanatory
 **/
trait XTypeTrait
{
    /**
     * @var array
     **/
    protected $_xTypeConfigCache;

    /**
     * Assign an array named "xType to manually set an xtype config. This is
     * much better than guessing the types
     *
     * @return array
     **/
    public function xTypeConfig()
    {
        $class = get_class($this);

        if (!isset(_XTypeTraitStorage::$typeCache[$class])) {
            $config = isset($this->xType) ? $this->xType : [];
            _XTypeTraitStorage::$typeCache[$class] = $this->bootXTypeConfig($config);
        }

        return _XTypeTraitStorage::$typeCache[$class];

    }

    /**
     * Boot the config if needed
     *
     * @param array $config
     *
     * @return array
     **/
    protected function bootXTypeConfig(array $config)
    {
        return $config;
    }
}

/**
 * Class _XTypeTraitStorage
 * @package Ems\XType\Eloquent
 * @internal
 */
final class _XTypeTraitStorage
{
    public static $typeCache = [];
}