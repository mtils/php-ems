<?php
/**
 *  * Created by mtils on 21.10.18 at 08:34.
 **/

namespace Ems\Contracts\Core;


use Iterator;
use function stream_get_meta_data;

/**
 * Interface Stream
 *
 * A stream represents a resource or handle when working with file systems
 * or connections and things like that.
 *
 * It's trying to be compatible to psr-7 streams.
 *
 * @package Ems\Contracts\Core
 */
interface Stream extends Connection, Iterator, Stringable
{

    // Constants for all meta data keys
    // @see https://www.php.net/manual/en/function.stream-get-meta-data.php

    /**
     * (bool)
     * @var string
     */
    const META_TIMED_OUT = 'timed_out';

    /**
     * (bool)
     * @var string
     */
    const META_BLOCKED = 'blocked';

    /**
     * (bool)
     * @var string
     */
    const META_AT_END = 'eof';

    /**
     * (int)
     * @var string
     */
    const META_UNREAD_BYTES = 'unread_bytes';

    /**
     * (string)
     * @var string
     */
    const META_STREAM_TYPE = 'stream_type';

    /**
     * (string)
     * @var string
     */
    const META_WRAPPER_TYPE = 'wrapper_type';

    /**
     * (mixed)
     * @var string
     */
    const META_WRAPPER_DATA = 'wrapper_data';

    /**
     * (bool)
     * @var string
     */
    const META_MODE = 'mode';

    /**
     * (bool)
     * @var string
     */
    const META_SEEKABLE = 'seekable';

    /**
     * (string)
     * @var string
     */
    const META_URI = 'uri';

    /**
     * Return the resource type. stream,ftp...
     *
     * @return string
     */
    public function type();

    /**
     * This is the read/write mode like r, r+, w, rw...
     *
     * @return string
     */
    public function mode();

    /**
     * Is this stream allowed to read?
     *
     * @return bool
     */
    public function isReadable();

    /**
     * Is this stream allowed to write/or can write at all
     *
     * @return bool
     */
    public function isWritable();

    /**
     * Return true if this stream can move its cursor freely.
     *
     * @return bool
     */
    public function isSeekable();

    /**
     * {@inheritdoc}
     *
     * @param int $position
     * @param int $whence (default: SEEK_SET)
     *
     * @return $this
     */
    public function seek($position, $whence=SEEK_SET);

    /**
     * Set the pointer to the end.
     *
     * @return $this
     */
    public function seekEnd();

    /**
     * Return true if stream will be opened asynchronous or not. This is
     * supported by the file wrappers and sockets.
     *
     * @return bool
     */
    public function isAsynchronous();

    /**
     * Set the stream to be unblocking (asynchronous) or blocking
     * (synchronous). Mostly supported on sockets and files.
     *
     * @param bool $asynchronous
     *
     * @return self
     */
    public function makeAsynchronous($asynchronous=true);

    /**
     * In opposite to blocking locking means that only one process can access
     * this stream/resource. File locking is an example.
     *
     * @return bool
     */
    public function isLocked();

    /**
     * Lock the stream / resource. (e.g. by file locking)
     *
     * @param bool|int $mode (LOCK_SH if true, put another int for more control)
     *
     * @return bool
     */
    public function lock($mode=true);

    /**
     * Unlock the stream / resource. (e.g. by file locking)
     *
     * @return bool
     */
    public function unlock();

    /**
     * Tell if the stream supports locking at all.
     *
     * @return bool
     */
    public function supportsLocking();

    /**
     * Find out if this stream is local or remote.
     *
     * @return bool
     */
    public function isLocal();

    /**
     * Find out if a stream is a tty.
     *
     * @return bool
     */
    public function isTerminalType();

    /**
     * Set the (network) timeout.
     *
     * @param int $timeout
     *
     * @return self
     */
    public function setTimeout($timeout);

    /**
     * Return the bytes which will be read in one iteration.
     *
     * @return int
     **/
    public function getChunkSize();

    /**
     * @see self::getChunkSize()
     *
     * @param int $chunkSize
     *
     * @return self
     **/
    public function setChunkSize($chunkSize);

    /**
     * Read a few bytes from the stream.
     *
     * @param int $length
     *
     * @return string
     */
    public function read($length);

    /**
     * Write (strings/data) into the stream. Perhaps another stream.
     *
     * @param mixed $data
     *
     * @return bool (For compatibility no int for written bytes)
     */
    public function write($data);

    /**
     * Return stream meta data. If the stream is not opened or the key does not
     * exist just return null.
     *
     * @see stream_get_meta_data()
     *
     * @param string $key (optional)
     *
     * @return mixed
     */
    public function metaData($key=null);
}