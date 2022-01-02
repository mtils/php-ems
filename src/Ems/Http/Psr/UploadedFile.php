<?php
/**
 *  * Created by mtils on 01.01.2022 at 15:48.
 **/

namespace Ems\Http\Psr;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

use function call_user_func;

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

    public function __construct(StreamInterface $stream, $size, int $error, callable $mover, string $clientFilename = null, string $clientMediaType=null)
    {
        $this->stream = $stream;
        $this->size = $size;
        $this->error = $error;
        $this->mover = $mover;
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

    public function moveTo($targetPath)
    {
        return call_user_func($this->mover, $this, $targetPath);
    }


    protected function isError() : bool
    {
        return $this->error !== UPLOAD_ERR_OK;
    }


}