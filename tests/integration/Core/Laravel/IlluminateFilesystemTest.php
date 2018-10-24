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
use function stream_context_get_default;

include_once __DIR__ .'/../LocalFilesystemTest.php';

class IlluminateFilesystemTest extends \Ems\Core\LocalFilesystemTest
{
    /**
     * @var string
     */
    protected $currentRoot = '';

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
     * @expectedException \Ems\Core\Exceptions\NotImplementedException
     */
    public function test_read_returns_contents_with_file_locking()
    {
        $fs = $this->newTestFilesystem();
        $contentsOfThisFile = file_get_contents(__FILE__);
        $this->assertEquals($contentsOfThisFile, $fs->read(__FILE__, 0, true));
    }

    /**
     * @expectedException \Ems\Core\Exceptions\NotImplementedException
     **/
    public function test_read_throws_exception_when_trying_to_read_a_locked_file()
    {
        $fs = $this->newTestFilesystem();
        $fileName = $this->tempFileName();
        $testString = 'Foo is a buddy of bar';

        $this->assertEquals(strlen($testString),
            $fs->write($fileName, $testString, LOCK_EX | LOCK_NB));
    }

    public function test_chrooted_baseUrl_leads_to_right_urls()
    {
        $structure = [
            'foo.txt'   => '',
            'bar.txt'   => '',
            'directory' => [
                'baz.xml'    => '',
                'users.json' => '',
                '2016'       => [
                    'gong.doc' => '',
                    'ho.odt'   => ''
                ]
            ]
        ];

        list($tempDir, $dirs) = $this->createNestedDirectories($structure);
        unset($dirs);
        $fs = $this->newTestFilesystem(['root' => $tempDir]);

        $filesAndFolders = $fs->listDirectory('/');

        $this->assertTrue($fs->isDirectory('/'));

        foreach ($structure as $basename=>$unused) {
            $fullPath = "/$basename";
            $this->assertTrue(in_array($fullPath, $filesAndFolders), "$fullPath was not contained in listDirectory");

            $url = "file://$tempDir$fullPath";

            $this->assertSame($basename == 'directory', $fs->isDirectory($fullPath));

            $this->assertEquals($url, (string)$fs->url($basename));

        }

        $filesAndFolders1 = $fs->listDirectory('/directory');

        foreach ($structure['directory'] as $basename=>$unused) {

            // pseudo full path (without fs url/prefix)
            $fullPath = "/directory/$basename";
            $this->assertTrue(in_array($fullPath, $filesAndFolders1), "$fullPath was not contained in listDirectory");

            $url = "file://$tempDir$fullPath";
            $this->assertEquals($url, (string)$fs->url($fullPath));

            $this->assertSame($basename == '2016', $fs->isDirectory($fullPath));

        }

        $filesAndFolders2 = $fs->listDirectory('/directory/2016');

        foreach ($structure['directory']['2016'] as $basename=>$unused) {

            // pseudo full path (without fs url/prefix)
            $fullPath = "/directory/2016/$basename";
            $this->assertTrue(in_array($fullPath, $filesAndFolders2), "$fullPath was not contained in listDirectory");

            $url = "file://$tempDir$fullPath";
            $this->assertEquals($url, (string)$fs->url($fullPath));

            $this->assertFalse($fs->isDirectory($fullPath));

        }

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
        return new IlluminateFilesystem($this->createLaravelAdapter($args));
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