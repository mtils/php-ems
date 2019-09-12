<?php
/**
 *  * Created by mtils on 08.09.19 at 07:10.
 **/

namespace Ems\Concurrency;


use DateTime;
use Ems\Contracts\Concurrency\Exceptions\PlannedTimeOverflowException;
use Ems\Contracts\Concurrency\Exceptions\ReleaseException;
use Ems\Contracts\Concurrency\Handle;
use Ems\Contracts\Concurrency\Manager;
use Ems\Contracts\Core\Filesystem;
use Ems\Contracts\Core\StringConverter;
use Ems\Core\Filesystem\FileStream;
use Ems\Core\LocalFilesystem;
use Ems\Core\StringConverter\AsciiStringConverter;
use function sys_get_temp_dir;
use function uniqid;
use function usleep;
use const LOCK_EX;
use const LOCK_NB;

class FlockManager extends AbstractManager implements Manager
{
    /**
     * @var string
     */
    private $lockFileDirectory = '';

    /**
     * @var StringConverter
     */
    private $fileNameConverter;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var array
     */
    private $streams = [];

    public function __construct(Filesystem $filesystem=null, StringConverter $converter=null, $tries=1, $delay=200)
    {
        $this->filesystem = $filesystem ?: new LocalFilesystem();
        $this->fileNameConverter = $converter ?: new AsciiStringConverter();
        $this->tries = $tries;
        $this->retryDelay = $delay;
    }

    /**
     * {@inheritDoc}
     *
     * TTL will be stored inside the handle.
     * I you pass a ttl the lock file will expire after this ttl.
     * In a redis server the lock would be just away.
     *
     * In the file system the lock file would remain in the file system so
     * in case of overflowing the lifetime we would hold the lock within
     * our process until it is finished even if we passed it.
     *
     * This can (should?) be interpreted as an error by other processes trying
     * to acquire the lock. Without frequently polling or checking lifetime in
     * the locking php script it is not possible to handle that situation so
     * it is a good practice to log the "too long running processes" somewhere.
     *
     *
     * @param string $uri
     * @param int $timeout (default:0)
     *
     * @return Handle|null
     *
     * @throws \Exception
     */
    public function lock($uri, $timeout = null)
    {

        $lockFilePath = $this->lockFilePath($uri);

        // Open the stream without a lock
        $stream = $this->filesystem->open($lockFilePath, 'a');

        $token = $this->loop(function () use ($stream) {
            if (!$stream->lock(LOCK_EX | LOCK_NB)) {
                return null;
            }
            return uniqid();
        });

        if (!$token) {
            return null;
        }

        $this->streams[$token] = $stream;

        return $this->createHandle($uri, $token, $timeout);

    }

    /**
     * {@inheritDoc}
     *
     * @param Handle $handle
     *
     * @return void
     * @throws \Exception
     */
    public function release(Handle $handle)
    {
        if (!isset($this->streams[$handle->token])) {
            throw new ReleaseException("Cannot release $handle->uri with token $handle->token. Seems not created by me.");
        }

        /** @var FileStream $stream */
        $stream = $this->streams[$handle->token];

        if (!$stream->unlock()) {
            throw new ReleaseException("Failure when unlocking uri $handle->uri under token $handle->token");
        }

        $stream->close();

        $this->filesystem->delete($this->lockFilePath($handle->uri));

    }

    /**
     * Get the directory were lock files will be stored.
     *
     * @return string
     */
    public function getLockFileDirectory()
    {
        if (!$this->lockFileDirectory) {
            $this->lockFileDirectory = sys_get_temp_dir();
        }
        return $this->lockFileDirectory;
    }

    /**
     * Set the directory where lock files will be stored.
     *
     * @param string $lockFileDirectory
     * @return FlockManager
     */
    public function setLockFileDirectory($lockFileDirectory)
    {
        $this->lockFileDirectory = rtrim($lockFileDirectory, '/');
        return $this;
    }

    /**
     * Create a new instance of your class with retry parameter assigned.
     * @param int $tries
     * @param int $delay
     * @param array $attributes (optional)
     * @return static
     */
    protected function replicate($tries, $delay, array $attributes = [])
    {
        return new static($this->filesystem, $this->fileNameConverter, $tries, $delay);
    }

    /**
     * @param string $uri
     *
     * @return string
     */
    protected function lockFilePath($uri)
    {
        return $this->getLockFileDirectory() . '/' . $this->uriToFilename($uri);
    }

    /**
     * @param string $uri
     *
     * @return string
     */
    protected function uriToFilename($uri)
    {
        return $this->fileNameConverter->convert($uri, 'FILENAME');
    }

}