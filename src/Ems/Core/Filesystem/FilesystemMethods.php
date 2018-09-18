<?php
/**
 *  * Created by mtils on 16.09.18 at 08:36.
 **/

namespace Ems\Core\Filesystem;


use function array_filter;

trait FilesystemMethods
{
    /**
     * Filter out file names by pattern and extensions.
     *
     * @param array  $paths
     * @param string $pattern
     * @param array  $extensions
     *
     * @return array
     */
    protected function filterPaths(array $paths, $pattern, array $extensions=[])
    {
        if (!$this->isFilterPattern($pattern) && !$extensions) {
            return $paths;
        }
        return array_filter($paths, function ($path) use ($pattern, $extensions) {
            return $this->filterPath($path, $pattern, $extensions);
        });
    }

    /**
     * Apply the filter on a single path.
     *
     * @param string $path
     * @param string $pattern
     * @param array  $extensions [optional]
     *
     * @return bool
     */
    protected function filterPath($path, $pattern, array $extensions=[])
    {
        $usePattern = $this->isFilterPattern($pattern);

        if (!$usePattern && !$extensions) {
            return true;
        }

        if ($usePattern && !fnmatch($pattern, $this->basename($path))) {
            return false;
        }

        if ($extensions && !in_array(strtolower($this->extension($path)), $extensions)) {
            return false;
        }

        return true;
    }

    /**
     * Check if a pattern really should filter.
     *
     * @param string $pattern
     *
     * @return bool
     */
    protected function isFilterPattern($pattern)
    {
        return $pattern && $pattern != '*';
    }
}