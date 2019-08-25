<?php
/**
 *  * Created by mtils on 25.08.19 at 10:39.
 **/

namespace Ems\Core\Support;


use Ems\Contracts\Core\Stream;
use Ems\Contracts\Core\Url as UrlContract;
use Ems\Core\Filesystem\FileStream;
use Psr\Log\LoggerInterface;
use function strtoupper;
use function var_export;

/**
 * Class StreamLogger
 *
 * This is a small placeholder logger to have very basic support of a logger. In
 * your application you possibly better use Monolog or something similar.
 *
 * @package Ems\Core\Support
 */
class StreamLogger implements LoggerInterface
{
    /**
     * @var Stream
     */
    protected $stream;

    /**
     * @var UrlContract
     */
    protected $url;

    /**
     * StreamLogger constructor.
     * @param string $target
     */
    public function __construct($target='php://stderr')
    {
        $this->setTarget($target);
    }

    /**
     * Return the target where should the log be written
     * @return Stream
     */
    public function getTarget()
    {
        return $this->stream;
    }

    /**
     * Set were the log should land.
     *
     * @param Stream|UrlContract|string $target
     *
     * @return $this
     */
    public function setTarget($target)
    {
        if ($target instanceof Stream) {
            $this->stream = $target;
            return $this;
        }
        $this->stream = new FileStream($target, 'a');
        return $this;
    }

    /**
     * System is unusable.
     *
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public function emergency($message, array $context = array())
    {
        $this->log(__FUNCTION__, $message, $context);
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
    public function alert($message, array $context = array())
    {
        $this->log(__FUNCTION__, $message, $context);
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
    public function critical($message, array $context = array())
    {
        $this->log(__FUNCTION__, $message, $context);
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
    public function error($message, array $context = array())
    {
        $this->log(__FUNCTION__, $message, $context);
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
    public function warning($message, array $context = array())
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * Normal but significant events.
     *
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public function notice($message, array $context = array())
    {
        $this->log(__FUNCTION__, $message, $context);
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
    public function info($message, array $context = array())
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * Detailed debug information.
     *
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public function debug($message, array $context = array())
    {
        $this->log(__FUNCTION__, $message, $context);
    }


    /**
     * Logs with an arbitrary level.
     *
     * @param string $level
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public function log($level, $message, array $context = array())
    {
        $entry = $this->format($level, $message, $context);
        $this->stream->write("\n$entry");
    }

    /**
     * Format a log entry.
     *
     * @param string $level
     * @param string $message
     * @param array $context
     *
     * @return string
     */
    protected function format($level, $message, array $context=[])
    {
        $date = date('Y-m-d H:i:s');

        $contextString = '';
        if ($context) {
            $contextString = var_export($context, true);
        }
        $type = strtoupper($level);
        return "## $date $type ## $message $contextString";
    }

}