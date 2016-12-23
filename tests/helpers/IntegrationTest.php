<?php

namespace Ems;


use Ems\Core\LocalFilesystem;
use Ems\Core\Application;

class IntegrationTest extends TestCase
{
    protected $purgeTempFiles = true;

    protected $_app;

    protected $_createdDirectories = [];

    public function tearDown()
    {
        $this->purgeTempFiles();
        parent::tearDown();
    }

    protected function newFilesystem()
    {
        return new LocalFilesystem();
    }

    protected function tempFile()
    {
        $tempDir = sys_get_temp_dir();
        return tempnam($tempDir, basename(__FILE__));
    }

    protected function tempFileName($extension='.tmp')
    {
        $tempDir = sys_get_temp_dir();
        $prefix = basename(str_replace('\\', '/', get_class($this)));
        return $tempDir.'/'.uniqid("$prefix-").$extension;
    }

    protected function tempDirName()
    {
        return $this->tempFileName('');
    }

    protected function tempDir()
    {
        $tempDirName = $this->tempDirName();
        $fs = $this->newFilesystem();
        $fs->makeDirectory($tempDirName, 0755, true, true);
        $this->_createdDirectories[] = $tempDirName;
        return $tempDirName;
    }

    protected function createNestedDirectories(array $structure, &$pathStructure=[], $tempDir=null)
    {
        $tempDir = $tempDir ? $tempDir : $this->tempDirName();
        $fs = $this->newFilesystem();
        $fs->makeDirectory($tempDir, 0755, true, true);

        foreach ($structure as $name=>$node) {
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

    public function app($binding=null, array $parameters=[])
    {
        if (!$this->_app) {
            $this->_app = new Application(realpath(__DIR__.'/../../'));
        }

        return ($binding ? $this->_app->__invoke($binding, $parameters) : $this->_app);
    }

    protected function purgeTempFiles()
    {
        if (!$this->_createdDirectories) {
            return;
        }

        if (!$this->purgeTempFiles) {
            $this->_createdDirectories = [];
            return;
        }

        $fs = $this->newFilesystem();

        foreach ($this->_createdDirectories as $dir) {
            $fs->delete($dir);
        }
    }
}
