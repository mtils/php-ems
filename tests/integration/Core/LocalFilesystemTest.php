<?php

namespace Ems\Core;

use Ems\Contracts\Core\Filesystem;
use Ems\Testing\FilesystemMethods;
use function in_array;

class LocalFilesystemTest extends \Ems\IntegrationTest
{
    use FilesystemMethods;

    public function test_implements_Interface()
    {
        $this->assertInstanceOf(
            'Ems\Contracts\Core\Filesystem',
            $this->newTestFilesystem()
        );
    }

    public function test_exists_return_true_on_dirs_and_files()
    {
        $fs = $this->newTestFilesystem();
        $this->assertTrue($fs->exists(__FILE__));
        $this->assertTrue($fs->exists(__DIR__));
    }

    public function test_exists_return_false_if_not_exists()
    {
        $this->assertFalse($this->newTestFilesystem()->exists('foo'));
    }

    public function test_contents_returns_file_content()
    {
        $fs = $this->newTestFilesystem();
        $contentsOfThisFile = file_get_contents(__FILE__);
        $this->assertEquals($contentsOfThisFile, $fs->contents(__FILE__));
    }

    public function test_contents_returns_head_if_bytes_are_passed()
    {
        $fs = $this->newTestFilesystem();
        $contentsOfThisFile = file_get_contents(__FILE__);
        $head = substr($contentsOfThisFile, 0, 80);
        $this->assertEquals($head, $fs->contents(__FILE__, 80));
    }

    /**
     * @expectedException \Ems\Core\Exceptions\ResourceNotFoundException
     **/
    public function test_contents_throws_NotFoundException_if_file_not_found()
    {
        $fs = $this->newTestFilesystem();
        $fs->contents('some-not-existing-file.txt');
    }

    public function test_contents_returns_contents_with_file_locking()
    {
        $fs = $this->newTestFilesystem();
        $contentsOfThisFile = file_get_contents(__FILE__);
        $this->assertEquals($contentsOfThisFile, $fs->contents(__FILE__, 0, true));
    }

    /**
     * @expectedException \Ems\Contracts\Core\Errors\ConcurrentAccess
     **/
    public function test_contents_throws_exception_when_trying_to_read_a_locked_file()
    {
        $fs = $this->newTestFilesystem();
        $fileName = $this->tempFileName();
        $testString = 'Foo is a buddy of bar';

        $this->assertEquals(strlen($testString), $fs->write($fileName, $testString, LOCK_EX | LOCK_NB));
        $resource = fopen($fileName, 'w');
        flock($resource, LOCK_EX | LOCK_NB);
        $this->assertEquals($testString, $fs->contents($fileName, 0, LOCK_SH | LOCK_NB));

//         $this->assertEquals($contentsOfThisFile, $fs->contents(__FILE__, 0, true));
    }

    public function test_write_writes_contents_to_file()
    {
        $testString = 'Foo is a buddy of bar';
        $fs = $this->newTestFilesystem();

        $fileName = sys_get_temp_dir().'/'.basename(__FILE__).'.tmp';

        $this->assertEquals(strlen($testString), $fs->write($fileName, $testString));
        $this->assertTrue(file_exists($fileName));
        $this->assertEquals($testString, $fs->contents($fileName));
        $fs->delete($fileName);
        $this->assertFalse($fs->exists($fileName));
    }

    public function test_delete_deletes_one_file()
    {
        $fs = $this->newTestFilesystem();
        $tempFile = $this->tempFile();

        $this->assertTrue($fs->exists($tempFile));
        $this->assertTrue($fs->delete($tempFile));
        $this->assertFalse($fs->exists($tempFile));
    }

    public function test_delete_deletes_many_files()
    {
        $fs = $this->newTestFilesystem();
        $count = 4;
        $tempFiles = [];

        for ($i=0; $i<$count; $i++) {
            $tempFiles[] = $this->tempFile();
        }

        foreach ($tempFiles as $tempFile) {
            $this->assertTrue($fs->exists($tempFile));
        }

        $this->assertTrue($fs->delete($tempFiles));

        foreach ($tempFiles as $tempFile) {
            $this->assertFalse($fs->exists($tempFile));
        }
    }

    public function test_delete_deletes_one_directory()
    {
        $dirName = $this->tempDirName();

        $fs = $this->newTestFilesystem();

        $this->assertTrue(mkdir($dirName));
        $this->assertTrue($fs->exists($dirName));
        $this->assertTrue($fs->delete($dirName));
        $this->assertFalse($fs->exists($dirName));
    }

    public function test_delete_deletes_nested_directory()
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
        $fs = $this->newTestFilesystem();

        $this->assertTrue($fs->exists($tempDir));
        $this->assertTrue($fs->delete($tempDir));
        $this->assertFalse($fs->exists($tempDir));
    }

    public function test_list_directory_lists_paths()
    {
        $fs = $this->newTestFilesystem();
        $structure = [
            'foo.txt' => '',
            'bar.txt' => '',
            'baz.txt' => ''
        ];

        list($tmpDir, $dirs) = $this->createNestedDirectories($structure);
        $listedDirs = $fs->listDirectory($tmpDir);

        sort($listedDirs);
        sort($dirs);
        $this->assertEquals($dirs, $listedDirs);
    }

    public function test_list_directory_lists_path_recursive()
    {
        $fs = $this->newTestFilesystem();
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

        list($tmpDir, $dirs) = $this->createNestedDirectories($structure);
        $listedDirs = $fs->listDirectory($tmpDir, true);

        sort($listedDirs);
        sort($dirs);

        $this->assertEquals($dirs, $listedDirs);
    }

    public function test_files_returns_only_files()
    {
        $fs = $this->newTestFilesystem();

        $structure = [
            'foo.txt'   => '',
            'bar.txt'   => '',
            'baz.txt'   => '',
            'directory' => []
        ];

        list($tmpDir, $dirs) = $this->createNestedDirectories($structure);

        $shouldBe = array_filter($dirs, function ($path) {
            return strpos($path, 'directory') === false;
        });

        sort($shouldBe);

        $files = $fs->files($tmpDir);

        sort($files);

        $this->assertEquals($shouldBe, $files);
    }

    public function test_files_returns_only_files_matching_pattern()
    {
        $fs = $this->newTestFilesystem();

        $structure = [
            'foo.txt'   => '',
            'bar.doc'   => '',
            'baz.txt'   => '',
            'directory' => []
        ];

        list($tmpDir, $dirs) = $this->createNestedDirectories($structure);

        $shouldBe = array_filter($dirs, function ($path) {
            return strpos($path, 'bar.doc') !== false;
        });

        sort($shouldBe);

        $files = $fs->files($tmpDir, '*.doc');

        sort($files);

        $this->assertEquals($shouldBe, $files);
    }

    public function test_files_returns_only_files_matching_extensions()
    {
        $fs = $this->newTestFilesystem();

        $structure = [
            'foo.txt'     => '',
            'bar.doc'     => '',
            'baz.txt'     => '',
            'hello.gif'   => '',
            'bye.PNG'     => '',
            'doc.doc.pdf' => ''
        ];

        list($tmpDir, $dirs) = $this->createNestedDirectories($structure);

        $shouldBe = array_filter($dirs, function ($path) {
            return strpos($path, 'bar.doc') !== false;
        });

        sort($shouldBe);

        $files = $fs->files($tmpDir, '*', 'doc');

        sort($files);

        $this->assertEquals($shouldBe, $files);

        $shouldBe = array_filter($dirs, function ($path) {
            return strpos($path, 'hello.gif') !== false || strpos($path, 'bye.PNG') !== false;
        });

        sort($shouldBe);

        $files = $fs->files($tmpDir, '*', ['gif', 'png']);

        sort($files);

        $this->assertEquals($shouldBe, $files);
    }

    public function test_directories_returns_only_directories_matching_pattern()
    {
        $fs = $this->newTestFilesystem();

        $structure = [
            'foo'       => [],
            'bar'       => [],
            'bar.txt'   => '',
            'directory' => [],
            'barely'    => []
        ];

        list($tmpDir, $dirs) = $this->createNestedDirectories($structure);

        $shouldBe = array_filter($dirs, function ($path) {
            return strpos($path, 'bar') !== false && strpos($path, 'bar.txt') === false;
        });

        sort($shouldBe);

        $directories = $fs->directories($tmpDir, '*bar*');

        sort($directories);

        $this->assertEquals($shouldBe, $directories);
    }

    public function test_directories_returns_only_directories()
    {
        $fs = $this->newTestFilesystem();

        $structure = [
            'foo'       => [],
            'bar'       => [],
            'baz.txt'   => '',
            'directory' => []
        ];

        list($tmpDir, $dirs) = $this->createNestedDirectories($structure);

        $shouldBe = array_filter($dirs, function ($path) {
            return strpos($path, 'baz.txt') === false;
        });

        sort($shouldBe);

        $directories = $fs->directories($tmpDir);

        sort($directories);

        $this->assertEquals($shouldBe, $directories);
    }

    public function test_copy_copies_file()
    {
        $fs = $this->newTestFilesystem();
        $structure = [
            'foo.txt' => '',
            'bar.txt' => '',
            'baz.txt' => ''
        ];

        list($tmpDir, $dirs) = $this->createNestedDirectories($structure);
        $listedDirs = $fs->listDirectory($tmpDir);

        sort($listedDirs);
        sort($dirs);
        $this->assertEquals($dirs, $listedDirs);

        $this->assertFalse($fs->exists("$tmpDir/foo2.txt"));
        $this->assertTrue($fs->copy("$tmpDir/foo.txt","$tmpDir/foo2.txt"));
        $this->assertTrue($fs->exists("$tmpDir/foo.txt"));
        $this->assertTrue($fs->exists("$tmpDir/foo2.txt"));
    }

    public function test_move_moves_file()
    {
        $fs = $this->newTestFilesystem();
        $structure = [
            'foo.txt' => '',
            'bar.txt' => '',
            'baz.txt' => ''
        ];

        list($tmpDir, $dirs) = $this->createNestedDirectories($structure);
        $listedDirs = $fs->listDirectory($tmpDir);

        sort($listedDirs);
        sort($dirs);
        $this->assertEquals($dirs, $listedDirs);

        $this->assertFalse($fs->exists("$tmpDir/foo2.txt"));
        $this->assertTrue($fs->move("$tmpDir/foo.txt","$tmpDir/foo2.txt"));
        $this->assertFalse($fs->exists("$tmpDir/foo.txt"));
        $this->assertTrue($fs->exists("$tmpDir/foo2.txt"));
    }

    public function test_link_links_file()
    {
        $fs = $this->newTestFilesystem();
        $structure = [
            'foo.txt' => '',
            'bar.txt' => '',
            'baz.txt' => ''
        ];

        list($tmpDir, $dirs) = $this->createNestedDirectories($structure);
        $listedDirs = $fs->listDirectory($tmpDir);

        sort($listedDirs);
        sort($dirs);
        $this->assertEquals($dirs, $listedDirs);

        $this->assertFalse($fs->exists("$tmpDir/foo2.txt"));
        $this->assertTrue($fs->link("$tmpDir/foo.txt","$tmpDir/foo2.txt"));
        $this->assertTrue($fs->exists("$tmpDir/foo.txt"));
        $this->assertTrue($fs->exists("$tmpDir/foo2.txt"));
        $this->assertEquals(Filesystem::TYPE_FILE, $fs->type("$tmpDir/foo.txt"));
        $this->assertEquals(Filesystem::TYPE_LINK, $fs->type("$tmpDir/foo2.txt"));
    }

    public function test_url_returns_root()
    {
        $fs = $this->newTestFileSystem();
        $url = $fs->url();
        $this->assertInstanceOf(\Ems\Contracts\Core\Url::class, $url);
        $this->assertEquals('file:///', "$url");
    }

    public function test_size_returns_size()
    {
        $fs = $this->newTestFileSystem();
        $this->assertGreaterThan(10, $fs->size(__FILE__));
    }

    public function test_supportedTypes_are_not_empty_and_contains_file()
    {
        $fs = $this->newTestFileSystem();
        $this->assertTrue(count($fs->supportedTypes()) > 0);
        $this->assertTrue(in_array(Filesystem::TYPE_FILE, $fs->supportedTypes()));
    }

    public function test_name_returns_only_name()
    {
        $fs = $this->newTestFilesystem();

        $tmpDir = $this->tempDir();
        $fs->write("$tmpDir/foo.txt", 'foo');
        $this->assertEquals('foo', $fs->name("$tmpDir/foo.txt"));
    }

    public function test_dirname_returns_only_name()
    {
        $fs = $this->newTestFilesystem();

        $tmpDir = $this->tempDir();
        $fs->write("$tmpDir/foo.txt", 'foo');
        $this->assertEquals($tmpDir, $fs->dirname("$tmpDir/foo.txt"));
    }

    public function test_mimeType_returns_mimeType()
    {
        $fs = $this->newTestFilesystem();

        $tmpDir = $this->tempDir();
        $fs->write("$tmpDir/foo.txt", 'foo');
        $this->assertEquals('text/plain', $fs->mimeType("$tmpDir/foo.txt"));
    }

    public function test_lastModified_returns_filemtime()
    {
        $fs = $this->newTestFilesystem();
        $this->assertEquals(filemtime(__FILE__), (int)$fs->lastModified(__FILE__)->format('U'));
    }

    public function test_type_returns_right_type()
    {
        $fs = $this->newTestFilesystem();

        $structure = [
            'foo'       => [],
            'bar'       => [],
            'baz.txt'   => '',
            'directory' => []
        ];

        list($tmpDir, $dirs) = $this->createNestedDirectories($structure);

        unset($dirs);
        $this->assertEquals(Filesystem::TYPE_FILE, $fs->type("$tmpDir/baz.txt"));
        $this->assertEquals(Filesystem::TYPE_DIR, $fs->type("$tmpDir/foo"));
        $this->assertEquals(Filesystem::TYPE_DIR, $fs->type("$tmpDir/bar"));

        $this->assertTrue($fs->makeDirectory("$tmpDir/test"));
        $this->assertEquals(Filesystem::TYPE_DIR, $fs->type("$tmpDir/test"));

    }

    protected function newTestFileSystem(array $args=[])
    {
        unset($args);
        return $this->newFilesystem();
    }
}
