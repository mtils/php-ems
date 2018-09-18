<?php
/**
 *  * Created by mtils on 16.09.18 at 07:21.
 **/

namespace Ems\Core\Laravel;


use DateTime;
use Ems\Contracts\Core\Filesystem;
use Ems\Contracts\Core\MimeTypeProvider;
use Ems\Contracts\Core\Url as UrlContract;
use Ems\Core\Exceptions\NotImplementedException;
use Ems\Core\Exceptions\ResourceNotFoundException;
use Ems\Core\Filesystem\FilesystemMethods;
use Ems\Core\ManualMimeTypeProvider;
use Ems\Core\PointInTime;
use Ems\Core\Url;
use Illuminate\Contracts\Filesystem\Cloud;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Filesystem\Filesystem as IlluminateFilesystemContract;
use Illuminate\Filesystem\FilesystemAdapter;
use function array_merge;
use function func_get_args;
use function is_array;
use function pathinfo;
use const PATHINFO_FILENAME;

class IlluminateFilesystem implements Filesystem
{
    use FilesystemMethods;

    /**
     * @var IlluminateFilesystemContract
     */
    protected $laravelFS;

    /**
     * @var MimeTypeProvider
     **/
    protected $mimeTypes;

    public function __construct(IlluminateFilesystemContract $laravelFS, MimeTypeProvider $mimeTypes=null)
    {
        $this->laravelFS = $laravelFS;
        $this->mimeTypes = $mimeTypes ?: new ManualMimeTypeProvider();
    }

    /**
     * Returns if a path exists.
     *
     * @param string $path
     *
     * @return bool
     **/
    public function exists($path)
    {
        return $this->laravelFS->exists($path);
    }

    /**
     * Return the (absolute) url to this filesystem or a path
     * inside it.
     *
     * @param string $path
     *
     * @return UrlContract
     */
    public function url($path = '/')
    {
        $url = $this->laravelFS instanceof Cloud ? new Url($this->laravelFS->url($path)) : new Url($path);

        if (!$url->scheme) {
            $url = $url->scheme('file');
        }
        return $url;

    }


    /**
     * Return the contents of a file.
     *
     * @param string $path
     * @param int $bytes (optional)
     * @param bool|int $lock (default:false) Enable locking or directly set the mode (LOCK_SH,...)
     *
     * @return string
     *
     * @throws ResourceNotFoundException
     * @throws NotImplementedException
     */
    public function contents($path, $bytes = 0, $lock = false)
    {
        if ($bytes !== 0 || $lock) {
            throw new NotImplementedException('Passing bytes or file locking is not supported by this filesystem.');
        }

        try {
            return $this->laravelFS->get($path);
        } catch (FileNotFoundException $e) {
            throw new ResourceNotFoundException("File $path not found.");
        }

    }

    /**
     * Write the contents $contents to the file in $path.
     *
     * @param string $path
     * @param string $contents
     * @param bool|int $lock (default:false) Enable locking or directly set the mode (LOCK_EX,...)
     * @param resource $handle (optional)
     *
     * @return int written bytes
     **/
    public function write($path, $contents, $lock = false, $handle = null)
    {
        if ($lock || $handle !== null) {
            throw new NotImplementedException('Locking and explicit passing of an handle is not supported by this filesystem.');
        }

        $result = $this->laravelFS->put($path, $contents);

        return $result ? $this->laravelFS->size($path) : 0;
    }


    /**
     * Read a whole file or just a few bytes of a file
     *
     * @param string $path
     * @param int $bytes (optional)
     * @param resource $handle (optional)
     *
     * @return string
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws NotImplementedException
     */
    public function read($path, $bytes = 0, $handle = null)
    {
        if ($bytes !== 0 || $handle !== null) {
            throw new NotImplementedException('Passing bytes or an handle is not supported by this filesystem.');
        }
        return $this->laravelFS->get($path);
    }

    /**
     * Return a file handle or throw an exception. (Mostly the same as fopen()).
     * Pass an array to get a stream context.
     *
     * @param string|array $pathOrContext
     * @param string $mode
     *
     * @throws NotImplementedException
     **/
    public function handle($pathOrContext, $mode = 'rb')
    {
        throw new NotImplementedException('getting handles is not supported by this filesystem.');
    }

    /**
     * Delete the path $path. Deletes directories, links and files.
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

            if ($this->isDirectory($path)) {
                $success = $this->laravelFS->deleteDirectory($path);
                continue;
            }
            $success = $this->laravelFS->delete($path);

        }

        return $success;
    }

    /**
     * Copy a file|directory.
     *
     * @param string $from
     * @param string $to
     *
     * @return bool
     **/
    public function copy($from, $to)
    {
        return $this->laravelFS->copy($from, $to);
    }

    /**
     * Move a file/dir.
     *
     * @param string $from
     * @param string $to
     *
     * @return bool
     **/
    public function move($from, $to)
    {
        return $this->laravelFS->move($from, $to);
    }

    /**
     * Create a (sym)link.
     *
     * @param string $from
     * @param string $to
     *
     * @throws NotImplementedException
     **/
    public function link($from, $to)
    {
        throw new NotImplementedException('Creating links is not supported by this filesystem');
    }

    /**
     * Returns the file size in bytes.
     *
     * @param string $path
     *
     * @return int
     **/
    public function size($path)
    {
        return $this->laravelFS->size($path);
    }

    /**
     * Returns the last modification test.
     *
     * @param string
     *
     * @return DateTime
     **/
    public function lastModified($path)
    {
        return PointInTime::guessFrom($this->laravelFS->lastModified($path));
    }

    /**
     * Return all names in an directory. Files and dirs.
     *
     * @param string $path
     * @param bool $recursive (optional)
     * @param bool $withHidden (optional)
     *
     * @return array
     **/
    public function listDirectory($path, $recursive = false, $withHidden = true)
    {
        $dirs = $this->formatDirsAndFiles($this->laravelFS->directories($path, $recursive));
        $files = $this->formatDirsAndFiles($this->laravelFS->files($path, $recursive));

        $dirsAndFiles = $this->mergeDirsAndFiles($dirs, $files);

        return $this->formatDirsAndFiles($dirsAndFiles);
    }

    /**
     * Return all files in $directory. Optionally filter by $pattern.
     * Return only files with extension $extension (optional).
     *
     * @param string $directory
     * @param string $pattern (optional)
     * @param string|array $extensions
     *
     * @return array
     **/
    public function files($directory, $pattern = '*', $extensions = '')
    {
        $files = $this->formatDirsAndFiles($this->laravelFS->files($directory));

        $extensions = $extensions ? (array)$extensions : [];

        return $this->filterPaths($files, $pattern, $extensions);
    }

    /**
     * Return all directories in $directory. Optionally filter by $pattern.
     *
     * @param string $directory
     * @param string $pattern (optional)
     *
     * @return array
     **/
    public function directories($directory, $pattern = '*')
    {
        $files = $this->formatDirsAndFiles($this->laravelFS->directories($directory));

        return $this->filterPaths($files, $pattern, []);
    }

    /**
     * Create a directory.
     *
     * @param string $path
     * @param int $mode
     * @param bool $recursive
     * @param bool $force
     *
     * @return bool
     **/
    public function makeDirectory($path, $mode = 0755, $recursive = true, $force = false)
    {
        if (func_num_args() > 1) {
            throw new NotImplementedException('This filesystem does not support options when creating a directory');
        }
        return $this->laravelFS->makeDirectory($path);
    }

    /**
     * Check if $path is a directory.
     *
     * @param string $path
     *
     * @return bool
     **/
    public function isDirectory($path)
    {
        $parentDir = $this->dirname($path);
        return in_array($path, $this->directories($parentDir));
    }

    /**
     * Check if $path is a file.
     *
     * @param string $path
     *
     * @return bool
     **/
    public function isFile($path)
    {
        $parentDir = $this->dirname($path);
        return in_array($path, $this->files($parentDir));
    }

    /**
     * Extract the filename of $path without its extension.
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
     * Extract the filename of $path with its extension.
     * According to the docs php does only string operations in basename and
     * does not touch the file system.
     *
     * @see http://php.net/manual/de/function.basename.php
     *
     * @param string $path
     *
     * @return string
     **/
    public function basename($path)
    {
        return basename($path);
    }

    /**
     * Extract the directory name of $path.
     *
     * @param string $path
     *
     * @return string
     **/
    public function dirname($path)
    {
        return dirname($path);
    }

    /**
     * Return the extension of $path.
     *
     * @param string $path
     *
     * @return string
     **/
    public function extension($path)
    {
        return mb_substr(mb_strrchr($this->basename($path), "."), 1);
    }

    /**
     * Return the type of $path.
     *
     * @param string $path
     *
     * @return string
     **/
    public function type($path)
    {
        if ($this->isDirectory($path)) {
            return Filesystem::TYPE_DIR;
        }

        return $this->isFile($path) ?  Filesystem::TYPE_FILE : Filesystem::TYPE_UNKNOWN;
    }

    /**
     * Return the type of $path.
     *
     * @param string $path
     *
     * @return string
     **/
    public function mimeType($path)
    {
        return $this->mimeTypes->typeOfName($path);
    }

    /**
     * Return the supported file (path) types of this filesystem
     *
     * @see self::TYPE_FILE, self::TYPE_DIRECTORY
     *
     * @return string[]
     */
    public function supportedTypes()
    {
        return [self::TYPE_DIR, self::TYPE_FILE];
    }


    /**
     * @param array $dirs
     *
     * @param array $files
     *
     * @return array
     */
    protected function mergeDirsAndFiles($dirs, $files)
    {

        return array_merge($dirs, $files);
    }

    /**
     * @param array $dirsAndFiles
     *
     * @return array
     */
    protected function formatDirsAndFiles($dirsAndFiles)
    {
        $formatted = [];

        foreach ($dirsAndFiles as $path) {
            $formatted[] = $this->toFullPath($path);
        }
        return $formatted;
    }

    /**
     * Make the path strings (pseudo) absolute.
     *
     * @param string $path
     *
     * @return string
     */
    protected function toFullPath($path)
    {
        if ($this->laravelFS instanceof FilesystemAdapter) {
            $path = $this->laravelFS->path($path);
        }
        return '/' . ltrim($path, '/');
    }
}