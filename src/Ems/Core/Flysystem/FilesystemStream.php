<?php
/**
 *  * Created by mtils on 24.10.18 at 06:51.
 **/

namespace Ems\Core\Flysystem;


use Ems\Contracts\Core\Stream;
use Ems\Contracts\Core\Type;
use Ems\Contracts\Core\Url as UrlContract;
use Ems\Core\Exceptions\NotImplementedException;
use Ems\Core\Exceptions\ResourceNotFoundException;
use Ems\Core\Filesystem\FileStream;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemOperationFailed;
use League\Flysystem\FilesystemOperator as FilesystemInterface;
use RuntimeException;

use function is_resource;

class FilesystemStream extends FileStream
{

    /**
     * @var FilesystemInterface
     */
    protected $flysystem;

    /**
     * @var bool
     */
    protected $didWrite = false;

    /**
     * FileStream constructor.
     *
     * @param FilesystemInterface $flysystem
     * @param UrlContract|string  $url
     * @param string              $mode (default:'r+')
     * @param bool                $lock (default:false)
     */
    public function __construct(FilesystemInterface $flysystem, $url, string $mode = 'r+', bool $lock = false)
    {
        $this->flysystem = $flysystem;
        if ($lock !== false) {
            throw new NotImplementedException('Locking is not supported by Flysystem Stream currently');
        }
        parent::__construct($url, $mode, $lock);
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $data
     *
     * @return int The amount of written bytes/rows/...
     *
     * @throws \League\Flysystem\FileExistsException
     * @throws FileNotFoundException
     */
    public function write($data)
    {

        $path = (string)$this->url();

        $isAppending = static::isAppendingMode($this->mode) || $this->didWrite;

        if (!is_resource($data) && !$data instanceof Stream && Type::isStringable($data)) {
            $this->didWrite = true;
            if ($isAppending) {
                throw new RuntimeException('Flysystem does not support appending strings');
            }
            $this->flysystem->write($path, $data);
            return $this->didWrite;
        }

        // Write while READING FROM A RESOURCE
        $readResource = $data instanceof Stream ? $data->resource() : $data;

        if (!is_resource($readResource)) {
            throw new RuntimeException('Cannot extract resource of ' . Type::of($data));
        }

        $this->didWrite = true;

        $this->flysystem->writeStream($path, $readResource);

        return $this->didWrite;

    }


    /**
     * {@inheritdoc}
     *
     * @return bool|string
     *
     * @throws ResourceNotFoundException
     */
    protected function readAll()
    {
        try {
            return $this->flysystem->read((string)$this->url);
        } catch (FileNotFoundException $e) {
            throw new ResourceNotFoundException($e->getMessage(), 0, $e);
        }
    }

    /**
     * This can only be used for read operations...
     *
     * @param string $filePath
     *
     * @return resource
     *
     * @throws ResourceNotFoundException
     */
    protected function createHandle($filePath)
    {
        try {
            return $this->flysystem->readStream($filePath);
        } catch (FilesystemOperationFailed $e) {
            throw new ResourceNotFoundException($e->getMessage(), 0, $e);
        }
    }

    /**
     * Manually concat the string to append to files. There is currently no other
     * solution with flysystem.
     *
     * @see https://github.com/thephpleague/flysystem/issues/90
     *
     * @param $data
     *
     * @return string
     */
    protected function concat($data)
    {
        return $this->readAll() . $data;
    }

}