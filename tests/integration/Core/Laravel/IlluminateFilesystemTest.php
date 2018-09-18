<?php
/**
 *  * Created by mtils on 17.09.18 at 11:47.
 **/

namespace Ems\Core\Laravel;

use Ems\Core\Exceptions\NotImplementedException;
use Illuminate\Contracts\Filesystem\Filesystem as IlluminateFilesystemContract;
use Illuminate\Filesystem\FilesystemAdapter;
use League\Flysystem\Adapter\Local as LocalFSAdapter;
use League\Flysystem\Filesystem as Flysystem;
use Mockery;

include_once __DIR__ .'/../LocalFilesystemTest.php';

class IlluminateFilesystemTest extends \Ems\Core\LocalFilesystemTest
{
    /**
     * @var string
     */
    protected $currentRoot = '';

    public function test_contents_returns_head_if_bytes_are_passed()
    {
        try {
            parent::test_contents_returns_head_if_bytes_are_passed();
            $boom = true;
            $this->fail('Passing bytes to ->contents() should fail');
        } catch (NotImplementedException $e) {
            $this->assertFalse(isset($boom));
        }
    }

    public function test_contents_returns_contents_with_file_locking()
    {
        try {
            parent::test_contents_returns_contents_with_file_locking();
            $boom = true;
            $this->fail('Passing locking to ->contents should fail');
        } catch (NotImplementedException $e) {
            $this->assertFalse(isset($boom));
        }
    }

    public function test_link_links_file()
    {
        try {
            parent::test_link_links_file();
            $boom = true;
            $this->fail('Links are not supported and should throw an exception');
        } catch (NotImplementedException $e) {
            $this->assertFalse(isset($boom));
        }
    }

    public function test_contents_throws_exception_when_trying_to_read_a_locked_file()
    {
        // This is not applicable because locking is not supported
    }

    public function test_url_of_non_cloud_filesystem()
    {
        /** @var IlluminateFilesystemContract $lfs */
        $lfs = Mockery::mock(IlluminateFilesystemContract::class);

        $fs = new IlluminateFilesystem($lfs);

        $this->assertEquals('file:///', (string)$fs->url());
    }

    /**
     * @expectedException \Ems\Core\Exceptions\NotImplementedException
     */
    public function test_write_throws_exception_if_should_be_locked()
    {
        $this->newTestFileSystem()->write('/', 'blue', true);
    }

    /**
     * @expectedException \Ems\Core\Exceptions\NotImplementedException
     */
    public function test_write_throws_exception_if_handle_passed()
    {
        $this->newTestFileSystem()->write('/', 'blue', false, true);
    }

    /**
     * @expectedException \Ems\Core\Exceptions\NotImplementedException
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function test_read_throws_exception_if_bytes_was_passed()
    {
        $this->newTestFileSystem()->read('/', 33);
    }

    /**
     * @expectedException \Ems\Core\Exceptions\NotImplementedException
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function test_read_throws_exception_if_handle_was_passed()
    {
        $this->newTestFileSystem()->read('/', 0, true);
    }

    /**
     * @expectedException \Ems\Core\Exceptions\NotImplementedException
     */
    public function test_handle_throws_NotImplementedException()
    {
        $this->newTestFileSystem()->handle('/');
    }

    /**
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function test_read_returns_file_content()
    {
        $fs = $this->newTestFilesystem();
        $contentsOfThisFile = file_get_contents(__FILE__);
        $this->assertEquals($contentsOfThisFile, $fs->read(__FILE__));
    }

    /**
     * @expectedException \Ems\Core\Exceptions\NotImplementedException
     */
    public function test_makeDirectory_throws_NotImplementedException_when_passing_mode()
    {
        $this->newTestFileSystem()->makeDirectory('/test', 655);
    }

    /**
     * Return a new Filesystem instance
     *
     * @param array $args
     *
     * @return IlluminateFilesystem
     **/
    protected function newTestFileSystem(array $args=[])
    {
        return new IlluminateFilesystem($this->createLaravelAdapter());
    }

    /**
     * @param array $args
     *
     * @return FilesystemAdapter
     */
    protected function createLaravelAdapter(array $args=[])
    {
        return new FilesystemAdapter($this->createFlysystem($args));
    }

    /**
     * @param array $args
     *
     * @return Flysystem
     */
    protected function createFlysystem(array $args=[])
    {
        $flySystem = new Flysystem($this->createFlysystemAdapter($args));
        $url = isset($args['url']) ? $args['url'] : '/';
        $flySystem->getConfig()->set('url', $url);
        return $flySystem;
    }

    /**
     * @param array $args
     *
     * @return LocalFSAdapter
     */
    protected function createFlysystemAdapter(array $args=[])
    {
        $root = isset($args['root']) ? $args['root'] : '/';
        return new LocalFSAdapter($root);
    }
}