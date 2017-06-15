<?php

namespace Ems\XType\Eloquent;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

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
        if (!isset($this->_xTypeConfigCache)) {
            $config = isset($this->xType) ? $this->xType : [];
            $this->_xTypeConfigCache = $this->bootXTypeConfig($config);
        }

        return $this->_xTypeConfigCache;
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