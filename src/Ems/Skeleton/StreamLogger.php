<?php
/**
 *  * Created by mtils on 25.08.19 at 10:39.
 **/

namespace Ems\Skeleton;


use Ems\Contracts\Skeleton\OutputConnection;
use Ems\Contracts\Core\Stream;
use Ems\Contracts\Core\Stringable;
use Ems\Contracts\Core\Url as UrlContract;
use Ems\Core\Filesystem\FileStream;
use Ems\Core\Support\LoggerMethods;
use Psr\Log\LoggerInterface;

use Throwable;

use function get_class;
use function is_object;
use function json_encode;
use function spl_object_id;
use function strtoupper;
use function var_export;

use const JSON_PRETTY_PRINT;

/**
 * Class StreamLogger
 *
 * This is a small placeholder logger to have very basic support of a logger. In
 * your application you possibly better use Monolog or something similar.
 *
 * @package Ems\Core\Support
 */
class StreamLogger implements LoggerInterface, OutputConnection
{
    use LoggerMethods;

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
     * {@inheritDoc}
     *
     * @param string|Stringable $output
     * @param bool $lock
     *
     * @return mixed
     */
    public function write($output, bool $lock = false)
    {
        return $this->getTarget()->write($output);
    }

    /**
     * {@inheritDoc}
     *
     * @return UrlContract
     **/
    public function url()
    {
        return $this->getTarget()->url();
    }

    /**
     * {@inheritDoc}
     *
     * @return bool
     **/
    public function isOpen()
    {
        return $this->getTarget()->isOpen();
    }

    /**
     * {@inheritDoc}
     *
     * @return self
     **/
    public function open()
    {
        $this->getTarget()->open();
        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @return self
     **/
    public function close()
    {
        $this->getTarget()->close();
        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @return resource|object|null
     **/
    public function resource()
    {
        return $this->getTarget()->resource();
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
            $contextString = $this->formatContext($context);
        }
        $type = strtoupper($level);
        return "## $date $type ## $message $contextString";
    }

    /**
     * Format the context of a log message.
     *
     * @param array $context
     * @return string
     */
    protected function formatContext(array $context) : string
    {
        $formatted = [];
        foreach ($context as $key=>$value) {
            if (is_array($value)) {
                $formatted[] = "$key => " . json_encode($value, JSON_PRETTY_PRINT, 4);
                continue;
            }
            if (!is_object($value)) {
                $formatted[] = "$key => " . var_export($value, true);
                continue;
            }
            if (!$value instanceof Throwable) {
                $formatted[] = "$key => Object #" . spl_object_id($value) . ' of class ' . get_class($value);
                continue;
            }

            $formatted[] = "$key => Exception " . get_class($value) . ' with message "' . $value->getMessage() . '"';
            $formatted[] = 'Trace: ' . $value->getTraceAsString();

        }

        return implode("\n", $formatted);
    }

}