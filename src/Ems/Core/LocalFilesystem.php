<?php

namespace Ems\Core;

use Ems\Contracts\Core\Exceptions\TypeException;
use Ems\Contracts\Core\Filesystem;
use Ems\Contracts\Core\MimeTypeProvider;
use Ems\Contracts\Core\Stream;
use Ems\Contracts\Core\Type;
use Ems\Contracts\Core\Url as UrlContract;
use Ems\Core\Exceptions\ResourceNotFoundException;
use Ems\Core\Filesystem\FileStream;
use Ems\Core\Filesystem\FilesystemMethods;
use Ems\Core\Filesystem\ResourceStream;
use Ems\Http\HttpFileStream;
use RuntimeException;
use Throwable;
use function class_exists;
use function file_get_contents;
use function is_resource;
use function stream_copy_to_stream;

class LocalFilesystem implements Filesystem
{
    use FilesystemMethods;

    /**
     * @var string
     */
    public static $directoryMimetype = 'inode/directory';

    /**
     * @var MimeTypeProvider
     **/
    protected $mimeTypes;

    /**
     * @param MimeTypeProvider $mimeTypes (optional)
     **/
    public function __construct(MimeTypeProvider $mimeTypes=null)
    {
        $this->mimeTypes = $mimeTypes ? $mimeTypes : new ManualMimeTypeProvider();
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
     * Return the (absolute) url to this filesystem or a path
     * inside it.
     *
     * @param string $path
     *
     * @return UrlContract
     */
    public function url($path = '/')
    {
        return new Url("file://$path");
    }


    /**
     * {@inheritdoc}
     *
     * @param string|resource  $source
     * @param int              $bytes  (optional)
     * @param bool|int         $lock (default:false) Enable locking or directly set the mode (LOCK_EX,...)
     *
     * @return string
     **/
    public function read($source, $bytes = 0, $lock = false)
    {
        $isString = Type::isStringLike($source);

        if ($isString && !$this->isFile($source)) {
            throw new ResourceNotFoundException("Path '$source' not found");
        }

        if ($isString && !$lock) {
            return $bytes ? file_get_contents($source, false, null, 0, $bytes) : file_get_contents($source);
        }

        $handlePassed = $source instanceof Stream || is_resource($source);

        if (!$handlePassed) {
            clearstatcache(true, $source);
        }

        $stream = $this->open($source, 'r', $lock);

        if (!$bytes) {
            return $stream->toString();
        }

        $string = $stream->read($bytes);

        if (!$handlePassed) {
            $stream->close();
        }

        return $string;

    }

    /**
     * @deprecated
     *
     * @param string   $path
     * @param int      $bytes (optional)
     * @param bool|int $lock (default:false) Enable locking or directly set the mode (LOCK_SH,...)
     *
     * @return string
     **/
    public function contents($path, $bytes = 0, $lock = false)
    {
        return $this->read($path, $bytes, $lock);
    }

    /**
     * {@inheritdoc}
     *
     * @param string|Stream|resource $target (also objects with __toString)
     * @param string|Stream|resource $contents (also objects with __toString)
     * @param bool|int               $lock (default:false) Enable locking or directly set the mode (LOCK_EX,...)
     *
     * @return bool
     **/
    public function write($target, $contents, $lock = false)
    {
        if ($target instanceof Stream) {
            return (bool)$target->write($contents);
        }

        if (is_bool($lock)) {
            $lock = $lock ? LOCK_EX : 0;
        }

        if (!$this->isStream($target) && !$this->isStreamContext($target)) {
            return (bool) file_put_contents($target, $contents, $lock);
        }

        if (Type::isStringLike($contents)) {
            return (bool)fwrite($target, $contents);
        }

        // $target and $contents are streams
        if ($this->isStream($contents)) {
            return (bool)stream_copy_to_stream($contents, $target);
        }

        throw new TypeException("Unsupported contents type: " . Type::of($contents));

    }

    /**
     * Open a stream to a url.
     *
     * @param Url|string|resource $uri
     * @param string              $mode (default:'r+')
     * @param bool                $lock (default:false)
     *
     * @return Stream
     */
    public function open($uri, $mode='r+', $lock=false)
    {

        if (is_resource($uri)) {
            return new ResourceStream($uri, $lock);
        }

        if (!Type::isStringLike($uri) && !$uri instanceof UrlContract) {
            throw new TypeException('$uri has to be string or ' . Type::of($uri));
        }

        $uri = $uri instanceof UrlContract ? $uri : new Url($uri);

        if ($uri->scheme == 'http' || $uri->scheme == 'https' && class_exists(HttpFileStream::class)) {
            return new HttpFileStream($uri, $mode, $lock);
        }

        return new FileStream($uri, $mode, $lock);
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
            } catch (Throwable $e) {
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

        $extensions = $extensions ? (array)$extensions : [];

        return $this->filterPaths($all, $pattern, $extensions);

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

        return $this->filterPaths($all, $pattern);
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
        if ($this->isDirectory($path)) {
            return static::$directoryMimetype;
        }
        return $this->mimeTypes->typeOfFile($path);
    }

    /**
     * {@inheritdoc}
     *
     * @return string[]
     */
    public function supportedTypes()
    {
        return [
            self::TYPE_FIFO,
            self::TYPE_CHAR,
            self::TYPE_DIR,
            self::TYPE_BLOCK,
            self::TYPE_LINK,
            self::TYPE_FILE,
            self::TYPE_SOCKET,
            self::TYPE_UNKNOWN
        ];
    }


    /**
     * {@inheritdoc}
     *
     * @deprecated
     *
     * @param string|array $pathOrContext
     * @param string       $mode (default: 'rb')
     *
     * @throws ResourceNotFoundException
     *
     * @return resource
     **/
    public function handle($pathOrContext, $mode = 'rb')
    {
        if (is_array($pathOrContext)) {
            return stream_context_create($pathOrContext);
        }
        if (!$handle = @fopen($pathOrContext, $mode)) {
            throw new ResourceNotFoundException("Path '$pathOrContext' cannot be opened");
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

    /**
     * Find out if the passed resource is a stream context resource.
     *
     * @param mixed $resource
     *
     * @return bool
     */
    protected function isStreamContext($resource)
    {
        return is_resource($resource) && get_resource_type($resource) == 'stream-context';
    }

    /**
     * Find out if the passed resource is a stream resource.
     *
     * @param mixed $resource
     *
     * @return bool
     */
    protected function isStream($resource)
    {
        return is_resource($resource) && get_resource_type($resource) == 'stream';
    }

    /**
     * @param string   $url
     * @param resource $context
     *
     * @return string
     */
    protected function getFromStreamContext($url, $context)
    {

        $level = error_reporting(0);
        $body = file_get_contents($url, 0, $context);

        error_reporting($level);

        if ($body === false) {
            $error = error_get_last();
            $message = isset($error['message']) && $error['message'] ? $error['message'] : "Cannot read from $url.";
            throw new RuntimeException($message);
        }



        if (isset($http_response_header)) {
            $header = implode("\r\n", $http_response_header);
            return "$header\r\n\r\n$body";
        }

        return $body;
    }

    /**
     * Read from an resource/handle.
     *
     * @param $handle
     * @param bool $closeAfter (default:true)
     *
     * @return string
     */
    protected function getFromResource($handle, $closeAfter=true)
    {
        $contents = '';

        while (!feof($handle)) {
            $contents .= fread($handle, 8192);
        }

        if ($closeAfter) {
            fclose($handle);
        }

        return $contents;
    }
}
