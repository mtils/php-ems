<?php


namespace Ems\Contracts\XType;

interface TypeFactory
{

    /**
     * Return if this factory can create an xtype out of
     * $config
     *
     * @param mixed $config
     * @return bool
     **/
    public function canCreate($config);

    /**
     * Builds a XType out of a config. Config can be anything
     *
     * @param mixed $config
     * @return \Ems\Contracts\XType\XType
     **/
    public function toType($config);
}
