<?php
/**
 *  * Created by mtils on 24.01.18 at 11:04.
 **/

namespace Ems\Contracts\Core;

/**
 * Interface Chatty
 *
 * This interface is for objects that emit messages.
 * An import or console action could be chatty an emit messages
 * while processing something.
 *
 * @package Ems\Contracts\Core
 */
interface Chatty
{
    /**
     * @var string
     **/
    const DEBUG = 'debug';

    /**
     * @var string
     **/
    const INFO = 'info';

    /**
     * @var string
     **/
    const WARNING = 'warning';

    /**
     * @var string
     **/
    const ERROR = 'error';

    /**
     * @var string
     **/
    const FATAL = 'fatal';

    /**
     * If your object is sad and nobody seems to here what it
     * has to say, assign a listener and make it glad.
     * Be nice to your objects.
     *
     * The first argument is a message, the second is a constant
     * value to classify the message (warning, error, ...)
     *
     * @param callable $listener
     */
    public function onMessage(callable $listener);
}