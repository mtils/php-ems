<?php
/**
 *  * Created by mtils on 24.10.18 at 06:51.
 **/

namespace Ems\Core\Flysystem;


use Ems\Contracts\Core\Stream;
use Ems\Contracts\Core\Type;
use Ems\Core\Exceptions\NotImplementedException;
use Ems\Core\Exceptions\ResourceNotFoundException;
use Ems\Core\Filesystem\FileStream;
use function is_resource;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;
use Ems\Contracts\Core\Url as UrlContract;
use RuntimeException;

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
            $this->didWrite = $isAppending ? $this->flysystem->update($path, $this->concat($data)) : $this->flysystem->write($path, $data);
            return $this->didWrite;
        }

        // Write while READING FROM A RESOURCE
        $readResource = $data instanceof Stream ? $data->resource() : $data;

        if (!is_resource($readResource)) {
            throw new RuntimeException('Cannot extract resource of ' . Type::of($data));
        }

        if ($isAppending) {
            $this->didWrite = $this->flysystem->updateStream($path, $readResource);
            return $this->didWrite;
        }

        $this->didWrite = $this->flysystem->writeStream($path, $readResource);

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
        } catch (FileNotFoundException $e) {
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