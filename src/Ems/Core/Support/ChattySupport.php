<?php
/**
 *  * Created by mtils on 02.02.18 at 14:45.
 **/

namespace Ems\Core\Support;
use function call_user_func;
use Ems\Contracts\Core\Chatty;


/**
 * Trait ChattySupport
 *
 * @package Ems\Core\Support
 *
 * @see \Ems\Contracts\Core\Chatty
 */
trait ChattySupport
{
    /**
     * @var array
     */
    protected $chattyListeners = [];

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
    public function onMessage(callable $listener)
    {
        $this->chattyListeners[] = $listener;
    }

    /**
     * Emit a message of $level to all listeners.
     *
     * @param string $message
     * @param string $level (default: Chatty::INFO)
     */
    protected function emitMessage($message, $level=Chatty::INFO)
    {
        foreach ($this->chattyListeners as $listener) {
            call_user_func($listener, $message, $level);
        }
    }
}