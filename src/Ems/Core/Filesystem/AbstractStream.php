<?php
/**
 *  * Created by mtils on 21.10.18 at 08:48.
 **/

namespace Ems\Core\Filesystem;


use Ems\Contracts\Core\Exceptions\TypeException;
use Ems\Contracts\Core\Stream;
use Ems\Contracts\Core\Type;
use Ems\Contracts\Core\Url as UrlContract;
use Ems\Core\Exceptions\NotImplementedException;
use Ems\Core\Exceptions\ResourceLockedException;
use Ems\Core\Helper;
use Ems\Core\Support\StringableTrait;
use Ems\Core\Url;
use function function_exists;
use LogicException;
use function flock;
use function fseek;
use function get_resource_type;
use function is_resource;
use function str_replace;
use function stream_copy_to_stream;
use function stream_get_meta_data;
use function stream_is_local;
use function stream_isatty;
use function stream_set_blocking;
use function stream_set_timeout;
use function stream_supports_lock;
use const LOCK_EX;
use const LOCK_UN;
use const SEEK_END;
use const SEEK_SET;

/**
 * Class AbstractStream
 *
 * This is a template class for streams.
 *
 * @package Ems\Core\Filesystem
 */
abstract class AbstractStream implements Stream
{
    use StringableTrait;

    /**
     * @var resource
     */
    protected $resource;

    /**
     * @var string
     */
    protected $mode = 'r+';

    /**
     * @var bool
     */
    protected $isAsynchronous;

    /**
     * @var bool
     */
    protected $shouldLock = false;

    /**
     * @var int
     **/
    protected $chunkSize = 4096;

    /**
     * @var int
     */
    protected $timeout = -1;

    /**
     * @var string
     **/
    protected $currentValue;

    /**
     * @var int
     **/
    protected $position = 0;

    /**
     * @var bool
     */
    protected $lockApplied = false;

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function type()
    {
        if (!is_resource($this->resource)) {
            return '';
        }
        return get_resource_type($this->resource);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function mode()
    {
        if (!$this->hasValidResource()) {
            return $this->mode;
        }
        return $this->metaData(static::META_MODE);
    }


    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function isReadable()
    {
        return static::isReadableMode($this->mode());
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function isWritable()
    {
        return static::isWritableMode($this->mode());
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function isAsynchronous()
    {
        if (!$this->hasValidResource()) {
            return $this->isAsynchronous;
        }

        return !(bool)$this->metaData(static::META_BLOCKED);
    }

    /**
     * Set the stream to be blocking. (Supported on sockets and files)
     *
     * @param bool $asynchronous
     *
     * @return self
     */
    public function makeAsynchronous($asynchronous = true)
    {
        $this->isAsynchronous = $asynchronous;

        if ($this->hasValidResource()) {
            $this->applyAsynchronous($this->resource);
        }

        return $this;

    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function isLocked()
    {

        if (!$this->supportsLocking()) {
            return false;
        }

        return $this->pathIsLocked((string)$this->url());
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function supportsLocking()
    {
        if (!$this->hasValidResource()) {
            return false;
        }
        return stream_supports_lock($this->resource);
    }

    /**
     * {@inheritdoc}
     *
     * @param bool $mode
     *
     * @return bool
     */
    public function lock($mode = true)
    {
        $this->shouldLock = $mode;

        if (!$handle = $this->resource()) {
            return false;
        }

        return $this->applyLock($handle);

    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function unlock()
    {
        $this->shouldLock = false;

        if (!$handle = $this->resource()) {
            return false;
        }

        return $this->applyLock($handle);
    }


    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function isLocal()
    {
        if($this->hasValidResource()) {
            return stream_is_local($this->resource);
        }
        if ($url = $this->url()) {
            return stream_is_local("$url");
        }
        return false;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function isTerminalType()
    {
        if(!$this->hasValidResource()) {
            return false;
        }

        if (function_exists('stream_isatty')) {
            /** @noinspection PhpVoidFunctionResultUsedInspection */
            return (bool)stream_isatty($this->resource);
        }

        return false;
    }

    /**
     * Set the (network) timeout.
     *
     * @param int $timeout
     *
     * @return self
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;

        if($this->hasValidResource() && $this->timeout !== -1) {
            stream_set_timeout($this->resource, $timeout);
        }

        return $this;
    }


    /**
     * Return the bytes which will be read in one iteration.
     *
     * @return int
     **/
    public function getChunkSize()
    {
        return $this->chunkSize;
    }

    /**
     * @see self::getChunkSize()
     *
     * @param int $chunkSize
     *
     * @return self
     **/
    public function setChunkSize($chunkSize)
    {
        $this->chunkSize = $chunkSize;

        return $this;
    }

    /**
     * Reset the internal pointer to the beginning.
     **/
    public function rewind()
    {
        // Lets assume rewind is only called when reading
        $this->failOnWriteOnly();

        $this->onRewind();
        $this->currentValue = $this->readNext(
            $this->initHandle(),
            $this->chunkSize
        );
        $this->position = $this->currentValue === null ? -1 : 0;
    }

    /**
     * @return string
     **/
    public function current()
    {
        return $this->currentValue;
    }

    /**
     * @return int
     **/
    public function key()
    {
        return $this->position;
    }

    public function next()
    {
        $this->currentValue = $this->readNext($this->resource, $this->chunkSize);
        $this->position = $this->currentValue === null ? -1 : $this->position + 1;
    }

    /**
     * @return bool
     **/
    public function valid()
    {
        return is_resource($this->resource) && $this->position !== -1;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function isSeekable()
    {
        return (bool)$this->metaData(static::META_SEEKABLE);
    }

    /**
     * {@inheritdoc}
     *
     * CAUTION: Using this method will mess up what key() returns.
     *
     * @param int $position
     * @param int $whence (default: SEEK_SET)
     *
     * @return $this
     */
    public function seek($position, $whence=SEEK_SET)
    {
        if (!$this->isSeekable()) {
            throw new NotImplementedException(Type::of($this) . ' is not seekable.');
        }
        fseek($this->resource(), $position, $whence);
        $this->next();
        return $this;
    }

    /**
     * Set the pointer to the end.
     *
     * @return $this
     */
    public function seekEnd()
    {
        return $this->seek(0, SEEK_END);
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $data
     *
     * @return bool (For compatibility no int for written bytes)
     */
    public function write($data)
    {

        if (is_resource($data)) {
            return (bool)stream_copy_to_stream($data, $this->resource());
        }

        $isStream = $data instanceof Stream;

        if (!$isStream && Type::isStringable($data)) {
            return (bool)fwrite($this->resource(), $data);
        }

        if (!$isStream) {
            throw new TypeException('Unsupported data type in write(): ' . Type::of($data));
        }

        $dataResource = $data->resource();

        if (is_resource($dataResource)) {
            return $this->write($dataResource);
        }

        $resource = $this->resource();

        $bytes = 0;

        foreach ($data as $chunk) {
            $bytes += fwrite($resource, $chunk);
        }

        return (bool)$bytes;

    }

    /**
     * @return self
     */
    public function open()
    {
        $this->resource();
        return $this;
    }

    /**
     * @return self
     */
    public function close()
    {
        if (is_resource($this->resource)) {
            fclose($this->resource);
        }
        $this->assignResource(null);
        return $this;
    }

    /**
     * @return bool
     */
    public function isOpen()
    {
        return $this->hasValidResource();
    }

    /**
     * @return resource
     */
    public function resource()
    {
        return $this->resource;
    }

    /**
     * @return UrlContract
     */
    public function url()
    {
        if (!$uri = $this->metaData(static::META_URI)) {
            return new Url();
        }

        return new Url($uri);
    }

    /**
     * {@inheritdoc}
     *
     * @see stream_get_meta_data()
     *
     * @param string $key (optional)
     *
     * @return mixed
     */
    public function metaData($key = null)
    {
        if (!$this->resource) {
            return null;
        }

        if (!$meta = stream_get_meta_data($this->resource)) {
            return null;
        }

        if (!$key) {
            return $meta;
        }

        return isset($meta[$key]) ? $meta[$key] : null;
    }

    /**
     * Read a few bytes from the stream.
     *
     * @param int $length
     *
     * @return string
     */
    public function read($length)
    {
        return $this->readFromHandle($this->resource(), $length);
    }


    /**
     * Renders this object. Without any exception problems you can render
     * the content here.
     *
     * @return string
     **/
    public function toString()
    {
        $this->failOnWriteOnly();

        if ($this->shouldLock === false) {
            return $this->readAll();

        }

        $path = (string)$this->url();

        // If the file was not locked by me (but someone else)
        if (!$this->lockApplied && $this->pathIsLocked($path)) {
            throw new ResourceLockedException("$path is locked");
        }

        return $this->readAll();

    }

    /**
     * {@inheritdoc}
     *
     * @return void
     * @link https://php.net/manual/en/language.oop5.decon.php
     */
    public function __destruct()
    {
        $this->close();
    }


    /**
     * Hook into the rewind call.
     **/
    protected function onRewind()
    {
    }

    /**
     * Read the next chunk and return it.
     *
     * @param resource $handle
     * @param int      $chunkSize
     *
     * @return string|null
     **/
    protected function readNext($handle, $chunkSize)
    {

        if (feof($handle)) {
            return null;
        }

        return $this->readFromHandle($handle, $chunkSize);
    }

    /**
     * @param resource $handle
     * @param int      $chunkSize
     *
     * @return bool|string
     */
    protected function readFromHandle($handle, $chunkSize)
    {
        return fread($handle, $chunkSize);
    }

    /**
     * Re-implement this method to allow fast toString/complete reading.
     *
     * @return string
     */
    protected function readAll()
    {
        $string = '';
        foreach ($this as $chunk) {
            $string .= $chunk;
        }
        return $string;
    }

    /**
     * Create or rewind the handle.
     *
     * @return resource
     **/
    protected function initHandle()
    {
        if ($this->isOpen()) {
            rewind($this->resource);

            return $this->resource;
        }

        return $this->resource();
    }

    /**
     * @return bool
     */
    protected function hasValidResource()
    {

        if (!is_resource($this->resource)) {
            return false;
        }

        if (!$type = get_resource_type($this->resource)) {
            return false;
        }

        return $type != 'Unknown';
    }

    /**
     * @return int
     */
    protected function getLockMode()
    {
        if ($this->isWritable()) {
            return LOCK_EX;
        }
        return LOCK_SH;
    }

    /**
     * @param resource|null $resource
     */
    protected function assignResource($resource)
    {
        $this->resource = $resource;
    }

    /**
     * @param resource $resource
     *
     * @return bool
     */
    protected function applyAsynchronous($resource)
    {
        return stream_set_blocking($resource, !$this->isAsynchronous);
    }

    /**
     * @param resource $resource
     *
     * @return bool
     */
    protected function applyLock($resource)
    {
        if ($this->shouldLock === false) {
            $this->lockApplied = false;
            return flock($resource, LOCK_UN);
        }

        $lock = $this->shouldLock === true ? $this->getLockMode() : $this->shouldLock;


        if (!flock($resource, $lock)) {
            return false;
        }

        $this->lockApplied = true;

        return true;
    }

    /**
     * @param resource $resource
     *
     * @return bool
     */
    protected function applyTimeout($resource)
    {
        return stream_set_timeout($resource, $this->timeout);
    }

    /**
     * @param resource $resource
     */
    protected function applySettings($resource)
    {

        if ($this->isAsynchronous !== null) {
            $this->applyAsynchronous($resource);
        }


        if ($this->shouldLock !== false) {
            $this->applyLock($resource);
        }

        if ($this->timeout !== -1) {
            $this->applyTimeout($resource);
        }
    }

    /**
     * @param $path
     * @param null $lockMode
     * @param string $openMode
     *
     * @return bool
     */
    protected function pathIsLocked($path, $lockMode = null, $openMode = 'r+')
    {
        $lockTestResource = fopen($path, $openMode);
        $lockMode = $lockMode ?: $this->getLockMode() | LOCK_NB;

        if (!flock($lockTestResource, $lockMode)) {
            return true;
        }

        // Remove my created lock.
        flock($lockTestResource, LOCK_UN);

        return false;

    }

    /**
     * @param string $mode
     *
     * @return bool
     */
    public static function isReadableMode($mode)
    {
        return Helper::contains(static::flagLess($mode), ['r', '+']);
    }

    /**
     * @param string $mode
     *
     * @return bool
     */
    public static function isWritableMode($mode)
    {
        return Helper::contains(static::flagLess($mode), ['+', 'w', 'a', 'x', 'c']);
    }

    /**
     * @param string $mode
     *
     * @return bool
     */
    public static function isAppendingMode($mode)
    {
        return Helper::contains(static::flagLess($mode), 'a');
    }

    /**
     * @param string $mode
     *
     * @return string
     */
    protected static function flagLess($mode)
    {
        // replace 'e', the close-on-exec Flag
        return str_replace(['e', 'b'], '', $mode);
    }

    protected function failOnWriteOnly()
    {
        if (!$this->isReadable()) {
            throw new LogicException('Cannot read from a write only stream');
        }
    }
}