<?php
/**
 *  * Created by mtils on 29.01.2022 at 16:04.
 **/

namespace Ems\Routing\SessionHandler;

use Ems\Contracts\Core\Filesystem;
use Ems\Core\LocalFilesystem;
use DateTime;
use SessionHandlerInterface;
use Ems\Contracts\Core\Errors\NotFound;

class FileSessionHandler implements SessionHandlerInterface
{
    /**
     * @var Filesystem
     */
    protected $fs;

    /**
     * @var string
     */
    protected $path = '';

    /**
     * @var bool
     */
    protected $wasInitialized = false;

    public function __construct(Filesystem $fs=null)
    {
        $this->fs = $fs ?: new LocalFilesystem();
    }

    /**
     * @param string $path
     * @param string $name
     * @return bool|void
     */
    public function open($path, $name)
    {
        $this->setPath($path);
        $this->init();
        return true;
    }

    /**
     * Read the session data.
     *
     * @param string $id
     * @return string
     */
    public function read($id) : string
    {
        $this->init();
        try {
            return (string)$this->fs->read($this->fileName($id));
        } catch (NotFound $e) {
            return '';
        }

    }

    /**
     * @param $id
     * @param $data
     * @return bool
     */
    public function write($id, $data) : bool
    {
        return !($this->fs->write($this->fileName($id), $data, true) === false);
    }

    /**
     * @return bool
     */
    public function close() : bool
    {
        return true;
    }

    /**
     * @param $id
     * @return bool
     */
    public function destroy($id) : bool
    {
        return $this->fs->delete($this->fileName($id));
    }

    /**
     * @param $max_lifetime
     * @return int
     */
    public function gc($max_lifetime) : int
    {
        $oldest = new DateTime();
        $oldest->setTimestamp(time()-$max_lifetime);
        $deleted = 0;
        foreach ($this->fs->files($this->path, 'session_*') as $file) {
            if ($this->fs->lastModified($file) < $oldest) {
                $this->fs->delete($file);
                $deleted++;
            }
        }
        return $deleted;
    }


    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @param string $path
     * @return FileSessionHandler
     */
    public function setPath(string $path): FileSessionHandler
    {
        $this->path = $path;
        $this->wasInitialized = false;
        return $this;
    }

    protected function init()
    {
        if ($this->wasInitialized) {
            return;
        }
        if (!$this->fs->isDirectory($this->path)) {
            $this->fs->makeDirectory($this->path, 0777);
            $this->wasInitialized = true;
        }
    }

    protected function fileName(string $sessionId) : string
    {
        return $this->path."/session_$sessionId.szd";
    }
}