<?php
/**
 *  * Created by mtils on 04.12.2021 at 14:38.
 **/

namespace Ems\Testing\Faker;

use Ems\Core\Exceptions\HandlerNotFoundException;
use Faker\Factory as BaseFactory;


class Factory extends BaseFactory
{
    /**
     * @var callable[]
     */
    protected static $factories = [];

    public static function create($locale = BaseFactory::DEFAULT_LOCALE)
    {
        $generator = new Generator();

        foreach (static::$defaultProviders as $provider) {
            $providerClassName = self::getProviderClassname($provider, $locale);
            $generator->addProvider(new $providerClassName($generator));
        }

        return $generator;
    }

    /**
     * Register a factory for $class.
     *
     * @param string $class
     * @param callable $factory
     * @return void
     */
    public static function setInstanceFactory(string $class, callable $factory)
    {
        static::$factories[$class] = $factory;
    }

    /**
     * Remove the factory for $class.
     *
     * @param string $class
     * @return void
     */
    public static function unsetInstanceFactory(string $class)
    {
        unset(static::$factories[$class]);
    }

    /**
     * Return if a factory for $class was assigned.
     *
     * @param string $class
     * @return bool
     */
    public static function hasInstanceFactory(string $class) : bool
    {
        return isset(static::$factories[$class]);
    }

    /**
     * Get the assigned factory for $class
     *
     * @param string $class
     * @return callable
     */
    public static function getInstanceFactory(string $class) : callable
    {
        if (isset(static::$factories[$class])) {
            return static::$factories[$class];
        }
        throw new HandlerNotFoundException("No factory registered for $class");
    }
}