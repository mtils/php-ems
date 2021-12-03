<?php
/**
 *  * Created by mtils on 28.11.2021 at 22:31.
 **/

namespace Ems\View;

use Ems\Contracts\Core\Filesystem;

use Ems\Core\LocalFilesystem;

use Ems\View\Exceptions\ViewNotFoundException;

use function array_unshift;
use function str_replace;

use const DIRECTORY_SEPARATOR;

/**
 * A simple class to find template files by a dotted view name syntax.
 */
class ViewFileFinder
{
    /**
     * @var array
     */
    protected $paths = [];

    /**
     * @var string
     */
    protected $extension = '.php';

    /**
     * @var Filesystem
     */
    protected $fs;

    /**
     * @param Filesystem|null $fs
     */
    public function __construct(Filesystem $fs=null)
    {
        $this->fs = $fs ?: new LocalFilesystem();
    }

    /**
     * Get the absolute file path to template(view) $name
     *
     * @param string $name
     * @return string
     */
    public function file(string $name) : string
    {
        $template = $this->viewToFile($name);
        foreach ($this->paths as $path) {
            $filePath = $path . "/$template";
            if ($this->fs->exists($filePath)) {
                return $filePath;
            }
        }
        throw new ViewNotFoundException($name, $this->paths);
    }

    /**
     * Get all configured paths.
     *
     * @return string[]
     */
    public function getPaths() : array
    {
        return $this->paths;
    }

    /**
     * Configure all template paths.
     *
     * @param array $paths
     * @return $this
     */
    public function setPaths(array $paths) :ViewFileFinder
    {
        $this->paths = $paths;
        return $this;
    }

    /**
     * Add a path to the view paths. The new path will be preferred.
     *
     * @param string $path
     * @return $this
     */
    public function addPath(string $path) : ViewFileFinder
    {
        array_unshift($this->paths, $path);
        return $this;
    }

    /**
     * Translate the view name $name (foo.bar) to a file name (foo/bar.php)
     *
     * @param string $name
     * @return string
     */
    protected function viewToFile(string $name) : string
    {
        return str_replace('.', DIRECTORY_SEPARATOR, $name) . $this->extension;
    }
}