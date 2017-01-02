<?php

namespace Ems\Core;

use Ems\Contracts\Core\Filesystem;
use Ems\Core\Exceptions\ResourceNotFoundException;
use Ems\Core\Exceptions\ResourceLockedException;
use ErrorException;

class LocalFilesystem implements FileSystem
{
    public function __construct()
    {
    }

    /**
     * {@inheritdoc}
     *
     * @param string $path
     *
     * @return bool
     **/
    public function exists($path)
    {
        return file_exists($path);
    }

    /**
     * {@inheritdoc}
     *
     * @param string   $path
     * @param int      $bytes (optional)
     * @param bool|int $lock (default:false) Enable locking or directly set the mode (LOCK_SH,...)
     *
     * @return bool
     **/
    public function contents($path, $bytes = 0, $lock = false)
    {
        if (!$this->isFile($path)) {
            throw new ResourceNotFoundException("Path '$path' not found");
        }

        if (!$lock) {
            return $this->getFileContents($path, $bytes);
        }

        $handle = $this->fileHandleOrFail($path);

        try {
            $lock = is_bool($lock) ? LOCK_SH : $lock;

            if (!flock($handle, $lock)) {
                throw new ResourceLockedException("Path '$path' is read locked by another process");
            }

            $contents = $this->getFileContents($path, $bytes, $handle);
            flock($handle, LOCK_UN);
        } finally {
            fclose($handle);
        }

        return $contents;
    }

    /**
     * {@inheritdoc}
     *
     * @param string   $path
     * @param string   $contents
     * @param bool|int $lock (default:false) Enable locking or directly set the mode (LOCK_EX,...)
     *
     * @return int written bytes
     **/
    public function write($path, $contents, $lock = false)
    {
        if (is_bool($lock)) {
            $lock = $lock ? LOCK_EX : 0;
        }
        return (int) file_put_contents($path, $contents, $lock);
    }

    /**
     * {@inheritdoc}
     *
     * @param string|array $path
     *
     * @return bool
     **/
    public function delete($path)
    {
        $paths = is_array($path) ? $path : func_get_args();

        $success = true;

        foreach ($paths as $path) {
            try {
                if ($this->isDirectory($path)) {
                    if (!$this->deleteDirectoryRecursive($path)) {
                        $success = false;
                    }
                    continue;
                }
                if (!@unlink($path)) {
                    $success = false;
                }
            } catch (ErrorException $e) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $from
     * @param string $to
     *
     * @return bool
     **/
    public function copy($from, $to)
    {
        return copy($from, $to);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $from
     * @param string $to
     *
     * @return bool
     **/
    public function move($from, $to)
    {
        return rename($from, $to);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $from
     * @param string $to
     *
     * @return bool
     **/
    public function link($from, $to)
    {
        return symlink($from, $to);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $path
     *
     * @return int
     **/
    public function size($path)
    {
        return filesize($path);
    }

    /**
     * {@inheritdoc}
     *
     * @param string
     *
     * @return \DateTime
     **/
    public function lastModified($path)
    {
        return \DateTime::createFromFormat('U', (string) filemtime($path));
    }

    /**
     * {@inheritdoc}
     *
     * @param string $path
     * @param bool   $recursive  (optional)
     * @param bool   $withHidden (optional)
     *
     * @return array
     **/
    public function listDirectory($path, $recursive = false, $withHidden = true)
    {
        if ($recursive) {
            return $this->listDirectoryRecursive($path);
        }

        $all = $withHidden ? glob($path.'/{,.}[!.,!..]*', GLOB_MARK | GLOB_BRACE) : glob("$path/*");

        $all = array_map(function ($path) {
            return rtrim($path, '/\\');
        }, $all);

        sort($all);

        return $all;
    }

    /**
     * {@inheritdoc}
     *
     * @param string       $directory
     * @param string       $pattern    (optional)
     * @param string|array $extensions
     *
     * @return array
     **/
    public function files($directory, $pattern = '*', $extensions = '')
    {
        $all = array_filter($this->listDirectory($directory), function ($path) {
            return $this->type($path) == 'file';
        });

        $fs = $this; //PHP 5.3

        if ($pattern != '*') {
            $all = array_filter($all, function ($path) use ($fs, $pattern) {
                $baseName = $fs->basename($path);

                return fnmatch($pattern, $fs->basename($path));
            });
        }

        if (!$extensions) {
            return $all;
        }

        $extensions = (array) $extensions;

        return array_filter($all, function ($path) use ($fs, $extensions) {
            return in_array(strtolower($fs->extension($path)), $extensions);
        });
    }

    /**
     * {@inheritdoc}
     *
     * @param string $directory
     * @param string $pattern   (optional)
     *
     * @return array
     **/
    public function directories($directory, $pattern = '*')
    {
        $all = array_filter($this->listDirectory($directory), function ($path) {
            return $this->type($path) == 'dir';
        });

        if ($pattern == '*') {
            return $all;
        }

        $fs = $this; //PHP 5.3

        return array_filter($all, function ($path) use ($fs, $pattern) {
            $baseName = $fs->basename($path);

            return fnmatch($pattern, $fs->basename($path));
        });
    }

    /**
     * {@inheritdoc}
     *
     * @param string $path
     * @param int    $mode
     * @param bool   $recursive
     * @param bool   $force
     *
     * @return bool
     **/
    public function makeDirectory($path, $mode = 0755, $recursive = true, $force = false)
    {
        if ($force) {
            return @mkdir($path, $mode, $recursive);
        }

        return mkdir($path, $mode, $recursive);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $path
     *
     * @return bool
     **/
    public function isDirectory($path)
    {
        return is_dir($path);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $path
     *
     * @return bool
     **/
    public function isFile($path)
    {
        return is_file($path);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $path
     *
     * @return string
     **/
    public function name($path)
    {
        return pathinfo($path, PATHINFO_FILENAME);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $path
     *
     * @return string
     **/
    public function basename($path)
    {
        return pathinfo($path, PATHINFO_BASENAME);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $path
     *
     * @return string
     **/
    public function dirname($path)
    {
        return pathinfo($path, PATHINFO_DIRNAME);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $path
     *
     * @return string
     **/
    public function extension($path)
    {
        return pathinfo($path, PATHINFO_EXTENSION);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $path
     *
     * @return string
     **/
    public function type($path)
    {
        return filetype($path);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $path
     *
     * @return string
     **/
    public function mimeType($path)
    {
        return finfo_file(finfo_open(FILEINFO_MIME_TYPE), $path);
    }

    /**
     * Read a whole file or just a few bytes of a file
     *
     * @param string   $path
     * @param int      $bytes
     * @param resource $handle (optional)
     *
     * @return string
     **/
    protected function getFileContents($path, $bytes, $handle = null)
    {
        if (!$bytes) {
            return file_get_contents($path);
        }

        list($handle, $handlePassed) = $handle ? [$handle, true]
                                               : [$this->fileHandleOrFail($path), false];

        clearstatcache(true, $path);

        $part = fread($handle, $bytes);

        if (!$handlePassed) {
            fclose($handle);
        }
        return $part;
    }

    /**
     * Get a file handle or throw an exception.
     *
     * @param string $path
     * @param string $mode (default: 'rb')
     *
     * @throws ResourceNotFoundException
     *
     * @return resource
     **/
    protected function fileHandleOrFail($path, $mode = 'rb')
    {
        if (!$handle = fopen($path, $mode)) {
            throw new ResourceNotFoundException("Path '$path' cannot be opened");
        }
        return $handle;
    }

    protected function deleteDirectoryRecursive($path)
    {
        if (!$this->isDirectory($path)) {
            return false;
        }

        $all = $this->listDirectory($path, true, true);

        // Sort by path length to resolve hierarchy conflicts
        usort($all, function ($a, $b) {
            return mb_strlen($b) - mb_strlen($a);
        });

        $success = true;

        foreach ($all as $nodePath) {
            if ($this->isDirectory($nodePath)) {
                if (!$this->deleteDirectory($nodePath)) {
                    $success = false;
                }
                continue;
            }

            // No directory
            if (!$this->delete($nodePath)) {
                $success = false;
            }
        }

        if (!$this->deleteDirectory($path)) {
            $success = false;
        }

        return $success;
    }

    protected function deleteDirectory($path)
    {
        return rmdir($path);
    }

    protected function listDirectoryRecursive($path, &$results = [])
    {
        foreach ($this->listDirectory(rtrim($path, '/\\')) as $filename) {
            $results[] = $filename;

            if (!$this->isDirectory($filename)) {
                continue;
            }

            $this->listDirectoryRecursive($filename, $results);
        }

        sort($results);

        return $results;
    }
}
