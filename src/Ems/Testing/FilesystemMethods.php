<?php

namespace Ems\Testing;

use Ems\Core\LocalFilesystem;

trait FilesystemMethods
{
    /**
     * @var array
     **/
    protected $_createdDirectories = [];

    /**
     * Return a new Filesystem instance
     *
     * @return LocalFilesystem
     **/
    protected function newFilesystem()
    {
        return new LocalFilesystem();
    }

    /**
     * Create a return a tempfile and return its path
     *
     * @return string
     **/
    protected function tempFile()
    {
        $tempDir = sys_get_temp_dir();
        return tempnam($tempDir, basename(__FILE__));
    }

    /**
     * Generate a temp file name and return its path
     *
     * @param string $extension (optional)
     *
     * @return string
     **/
    protected function tempFileName($extension='.tmp')
    {
        $tempDir = sys_get_temp_dir();
        $prefix = basename(str_replace('\\', '/', get_class($this)));
        return $tempDir.'/'.uniqid("$prefix-").$extension;
    }

    /**
     * Generate a temp dirname and return its name
     *
     * @return string
     **/
    protected function tempDirName()
    {
        return $this->tempFileName('');
    }

    /**
     * Create a temporaray directory and return its name
     *
     * @return string
     **/
    protected function tempDir()
    {
        $tempDirName = $this->tempDirName();
        $fs = $this->newFilesystem();
        $fs->makeDirectory($tempDirName, 0755, true, true);
        $this->_createdDirectories[] = $tempDirName;
        return $tempDirName;
    }

    /**
     * Outputs the files of a directory
     *
     * @param string $path
     * @param bool   $recursive (default:false)
     */
    protected function dumpDirectory($path, $recursive=false, $indent=0)
    {
        if (!$indent) {
            echo "\nDirectory: $path ----------------------------------";
        }

        $hit = false;

        $indention = str_repeat(' ', $indent);

        foreach (scandir($path) as $file) {

            if ($file == '.' || $file == '..') {
                continue;
            }

            $filePath = realpath("$path/$file");

            if (is_dir($filePath) && $recursive) {
                echo "\n$indention$file/";
                $this->dumpDirectory($filePath, true, $indent+4);
                $hit = true;
                continue;
            }

            $hit = true;
            echo "\n$indention$file";
        }

        if (!$hit) {
            echo "\n$indention(empty)";
        }
    }

    /**
     * Create a directory structure by a nested array. Every string creates a
     * file, every array a directory.
     *
     * @example [
     *     'foo.txt'
     *     'bar.txt',
     *     [
     *         'baz.xml',
     *         'users.json'
     *     ],
     *     'blank.gif'
     * ]
     *
     * @param array  $structure
     * @param array  $pathStructure
     * @param string $tempDir (optional)
     *
     * @return array
     **/
    protected function createNestedDirectories(array $structure, &$pathStructure=[], $tempDir=null)
    {
        $tempDir = $tempDir ? $tempDir : $this->tempDirName();
        $fs = $this->newFilesystem();
        $fs->makeDirectory($tempDir, 0755, true, true);

        foreach ($structure as $name=> $node) {
            $path = "$tempDir/$name";

            if (!is_array($node)) {
                $fs->write($path, '');
                $pathStructure[] = $path;
                continue;
            }

            $fs->makeDirectory($path);
            $pathStructure[] = $path;
            $this->createNestedDirectories($node, $pathStructure, $path);
        }
        $this->_createdDirectories[] = $tempDir;
        return [$tempDir, $pathStructure];
    }

    /**
     * @after
     **/
    protected function purgeTempFiles()
    {
        if (!$this->_createdDirectories) {
            return;
        }

        if (!$this->shouldPurgeTempFiles()) {
            $this->_createdDirectories = [];
            return;
        }

        $fs = $this->newFilesystem();

        foreach ($this->_createdDirectories as $dir) {
            $fs->delete($dir);
        }
    }

    /**
     * Return if all created directories of this test should be deleted
     * (Just add a property $shouldPurgeTempFiles by default)
     *
     * @return bool
     **/
    protected function shouldPurgeTempFiles()
    {
        if (!property_exists($this, 'shouldPurgeTempFiles')) {
            return true;
        }
        return $this->shouldPurgeTempFiles;
    }

    /**
     * @param string $dir
     * @param array  $results (optional)
     *
     * @return array
     **/
    protected function scandirRecursive($dir,  &$results = [])
    {
        $files = scandir($dir);

        foreach($files as $key => $value){
            $path = realpath($dir.DIRECTORY_SEPARATOR.$value);
            if(!is_dir($path)) {
                $results[] = $path;
            } else if($value != "." && $value != "..") {
                getDirContents($path, $results);
                $results[] = $path;
            }
        }

        return $results;
    }
}
