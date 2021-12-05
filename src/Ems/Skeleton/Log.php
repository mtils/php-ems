<?php
/**
 *  * Created by mtils on 05.12.2021 at 13:34.
 **/

namespace Ems\Skeleton;

use Ems\Core\Application;
use Psr\Log\LoggerInterface;

/**
 * This is a simple helper for short syntax logging
 */
class Log
{
    /**
     * System is unusable.
     *
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public static function emergency(string $message, array $context = [])
    {
        Application::container(LoggerInterface::class)->emergency($message, $context);
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public static function alert(string $message, array $context = [])
    {
        Application::container(LoggerInterface::class)->alert($message, $context);
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public static function critical(string $message, array $context = [])
    {
        Application::container(LoggerInterface::class)->critical($message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public static function error(string $message, array $context = [])
    {
        Application::container(LoggerInterface::class)->error($message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public static function warning(string $message, array $context = [])
    {
        Application::container(LoggerInterface::class)->warning($message, $context);
    }

    /**
     * Normal but significant events.
     *
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public static function notice(string $message, array $context = [])
    {
        Application::container(LoggerInterface::class)->notice($message, $context);
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public static function info(string $message, array $context = [])
    {
        Application::container(LoggerInterface::class)->info($message, $context);
    }

    /**
     * Detailed debug information.
     *
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public static function debug(string $message, array $context = [])
    {
        Application::container(LoggerInterface::class)->debug($message, $context);
    }
}