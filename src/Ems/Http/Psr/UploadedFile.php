<?php
/**
 *  * Created by mtils on 01.01.2022 at 15:48.
 **/

namespace Ems\Http\Psr;

use Ems\Core\Exceptions\UnsupportedParameterException;
use Ems\Core\Filesystem\FileStream;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

use function call_user_func;

use function move_uploaded_file;
use function php_sapi_name;

use const UPLOAD_ERR_OK;

class UploadedFile implements UploadedFileInterface
{
    /**
     * @var StreamInterface
     */
    private $stream;

    /**
     * @var int
     */
    private $error;

    /**
     * @var int
     */
    private $size = -1;

    /**
     * @var string|null
     */
    private $clientFilename = '';

    /**
     * @var string|null
     */
    private $clientMediaType = '';

    /**
     * @var callable
     */
    private $mover;

    /**
     * @var callable
     */
    private static $defaultMover;

    public function __construct(StreamInterface $stream, $size, int $error, string $clientFilename = null, string $clientMediaType=null)
    {
        $this->stream = $stream;
        $this->size = $size;
        $this->error = $error;
        $this->clientFilename = $clientFilename;
        $this->clientMediaType = $clientMediaType;
    }

    public function getStream() : StreamInterface
    {
        return $this->stream;
    }

    public function getSize()
    {
        return $this->size;
    }

    public function getError() : int
    {
        return $this->error;
    }

    public function getClientFilename()
    {
        return $this->clientFilename;
    }

    public function getClientMediaType()
    {
        return $this->clientMediaType;
    }

    /**
     * To be honest...please do not use this method. For me, it makes no sense
     * to have such a functionality in a value object. It will work to be compliant
     * to psr but a custom filesystem should also be possible...
     *
     * @param $targetPath
     * @return void
     */
    public function moveTo($targetPath)
    {
        call_user_func($this->getMover(), $this, $targetPath);
    }

    /**
     * @return callable
     */
    public function getMover(): callable
    {
        if (!$this->mover) {
            return self::getDefaultMover();
        }
        return $this->mover;
    }

    /**
     * @param callable $mover
     * @return UploadedFile
     */
    public function setMover(callable $mover): UploadedFile
    {
        $this->mover = $mover;
        return $this;
    }

    /**
     * Get or create the default mover for all UploadedFileInstances
     *
     * @return callable
     */
    public static function getDefaultMover() : callable
    {
        if (self::$defaultMover) {
            return self::$defaultMover;
        }
        self::$defaultMover = function (UploadedFile $file, $targetPath) {

            $stream = $file->getStream();
            if (!$stream instanceof FileStream) {
                throw new UnsupportedParameterException("The default implementation only works with FileStream");
            }

            if (php_sapi_name() === 'cli') {
                rename((string)$stream->url(), $targetPath);
                return;
            }
            move_uploaded_file((string)$stream->url(), $targetPath);
        };
        return self::$defaultMover;
    }

    /**
     * Set the default mover for all created UploadedFile instances.
     *
     * @param callable $mover
     * @return void
     */
    public static function setDefaultMover(callable $mover)
    {
        self::$defaultMover = $mover;
    }

    protected function isError() : bool
    {
        return $this->error !== UPLOAD_ERR_OK;
    }


}