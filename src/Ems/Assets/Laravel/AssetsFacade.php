<?php

namespace Ems\Assets\Laravel;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Illuminate\Routing\UrlGenerator
 */
class AssetsFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'Ems\Contracts\Assets\Manager';
    }

    /**
     * A readable alias for Assets::import().
     *
     * @param string $asset
     * @param string $groupName (optional)
     **/
    public static function js($asset, $groupName = null)
    {
        return static::getFacadeRoot()->import($asset, $groupName);
    }

    /**
     * A readable alias for Assets::import().
     *
     * @param string $asset
     * @param string $groupName (optional)
     **/
    public static function css($asset, $groupName = null)
    {
        return static::getFacadeRoot()->import($asset, $groupName);
    }

    /**
     * A readable alias for Assets::import().
     *
     * @param string $asset
     * @param string $groupName (optional)
     **/
    public static function icon($asset, $groupName = null)
    {
        return static::getFacadeRoot()->import($asset, $groupName);
    }
}
