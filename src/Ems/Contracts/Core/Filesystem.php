<?php

namespace Ems\Contracts\Core;

interface Filesystem
{
    /**
     * @var string
     */
    const TYPE_FIFO = 'fifo';

    /**
     * @var string
     */
    const TYPE_CHAR = 'char';

    /**
     * @var string
     */
    const TYPE_DIR = 'dir';

    /**
     * @var string
     */
    const TYPE_BLOCK = 'block';

    /**
     * @var string
     */
    const TYPE_LINK = 'link';

    /**
     * @var string
     */
    const TYPE_FILE = 'file';

    /**
     * @var string
     */
    const TYPE_SOCKET = 'socket';

    /**
     * @var string
     */
    const TYPE_UNKNOWN = 'unknown';

    /**
     * Returns if a path exists.
     *
     * @param string $path
     *
     * @return bool
     **/
    public function exists($path);

    /**
     * Return the (absolute) url to this filesystem or a path
     * inside it.
     *
     * @param string $path
     *
     * @return Url
     */
    public function url($path='/');

    /**
     * Read a whole file or just a few bytes of a file.
     *
     * @param string|Stream|resource $source
     * @param int                    $bytes (optional)
     * @param bool|int               $lock (default:false) Enable locking or directly set the mode (LOCK_EX,...)
     *
     * @return string
     **/
    public function read($source, $bytes = 0, $lock = false);

    /**
     * Write the contents $contents to the file in $path. Pass a resource for
     * in $contents to read from a stream. Optionally pass a $writeHandle to
     * have even more control over writing or continued writes.
     *
     * @param string|Stream|resource $target (also objects with __toString)
     * @param string|Stream|resource $contents (also objects with __toString)
     * @param bool|int               $lock (default:false) Enable locking or directly set the mode (LOCK_EX,...)
     *
     * @return bool
     **/
    public function write($target, $contents, $lock = false);

    /**
     * Open a stream to a url.
     *
     * @param Url|string|resource $uri
     * @param string              $mode (default:'r+')
     * @param bool                $lock (default:false)
     *
     * @return Stream
     */
    public function open($uri, $mode='r+', $lock=false);

    /**
     * Delete the path $path. Deletes directories, links and files.
     *
     * @param string|array $path
     *
     * @return bool
     **/
    public function delete($path);

    /**
     * Copy a file|directory.
     *
     * @param string $from
     * @param string $to
     *
     * @return bool
     **/
    public function copy($from, $to);

    /**
     * Move a file/dir.
     *
     * @param string $from
     * @param string $to
     *
     * @return bool
     **/
    public function move($from, $to);

    /**
     * Create a (sym)link.
     *
     * @param string $from
     * @param string $to
     *
     * @return bool
     **/
    public function link($from, $to);

    /**
     * Returns the file size in bytes.
     *
     * @param string $path
     *
     * @return int
     **/
    public function size($path);

    /**
     * Returns the last modification test.
     *
     * @param string
     *
     * @return \DateTime
     **/
    public function lastModified($path);

    /**
     * Return all names in an directory. Files and dirs.
     *
     * @param string $path
     * @param bool   $recursive  (optional)
     * @param bool   $withHidden (optional)
     *
     * @return array
     **/
    public function listDirectory($path, $recursive = false, $withHidden = true);

    /**
     * Return all files in $directory. Optionally filter by $pattern.
     * Return only files with extension $extension (optional).
     *
     * @param string       $directory
     * @param string       $pattern    (optional)
     * @param string|array $extensions
     *
     * @return array
     **/
    public function files($directory, $pattern = '*', $extensions = '');

    /**
     * Return all directories in $directory. Optionally filter by $pattern.
     *
     * @param string $directory
     * @param string $pattern   (optional)
     *
     * @return array
     **/
    public function directories($directory, $pattern = '*');

    /**
     * Create a directory.
     *
     * @param string $path
     * @param int    $mode
     * @param bool   $recursive
     * @param bool   $force
     *
     * @return bool
     **/
    public function makeDirectory($path, $mode = 0755, $recursive = true, $force = false);

    /**
     * Check if $path is a directory.
     *
     * @param string $path
     *
     * @return bool
     **/
    public function isDirectory($path);

    /**
     * Check if $path is a file.
     *
     * @param string $path
     *
     * @return bool
     **/
    public function isFile($path);

    /**
     * Extract the filename of $path without its extension.
     *
     * @param string $path
     *
     * @return string
     **/
    public function name($path);

    /**
     * Extract the filename of $path with its extension.
     *
     * @param string $path
     *
     * @return string
     **/
    public function basename($path);

    /**
     * Extract the dirname of $path.
     *
     * @param string $path
     *
     * @return string
     **/
    public function dirname($path);

    /**
     * Return the extension of $path.
     *
     * @param string $path
     *
     * @return string
     **/
    public function extension($path);

    /**
     * Return the type of $path.
     *
     * @param string $path
     *
     * @return string
     **/
    public function type($path);

    /**
     * Return the type of $path.
     *
     * @param string $path
     *
     * @return string
     **/
    public function mimeType($path);

    /**
     * Return the supported file (path) types of this filesystem
     *
     * @see self::TYPE_FILE, self::TYPE_DIRECTORY
     *
     * @return string[]
     */
    public function supportedTypes();

}
