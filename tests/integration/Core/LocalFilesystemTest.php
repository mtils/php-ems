<?php


namespace Ems\Core;



class LocalFilesystemTest extends \Ems\IntegrationTest
{
    public function test_implements_Interface()
    {
        $this->assertInstanceOf(
            'Ems\Contracts\Core\Filesystem',
            $this->newFilesystem()
        );
    }

    public function test_exists_return_true_on_dirs_and_files()
    {
        $fs = $this->newFilesystem();
        $this->assertTrue($fs->exists(__FILE__));
        $this->assertTrue($fs->exists(__DIR__));
    }

    public function test_exists_return_false_if_not_exists()
    {
        $fs = $this->newFilesystem();
        $this->assertFalse($this->newFilesystem()->exists('foo'));
    }

    public function test_contents_returns_file_content()
    {
        $fs = $this->newFilesystem();
        $contentsOfThisFile = file_get_contents(__FILE__);
        $this->assertEquals($contentsOfThisFile, $fs->contents(__FILE__));
    }

    public function test_contents_returns_head_if_bytes_are_passed()
    {
        $fs = $this->newFilesystem();
        $contentsOfThisFile = file_get_contents(__FILE__);
        $head = substr($contentsOfThisFile, 0, 80);
        $this->assertEquals($head, $fs->contents(__FILE__, 80));
    }

    /**
     * @expectedException \Ems\Core\Exceptions\ResourceNotFoundException
     **/
    public function test_contents_throws_NotFoundException_if_file_not_found()
    {
        $fs = $this->newFilesystem();
        $fs->contents('some-not-existing-file.txt');
    }

    public function test_write_writes_contents_to_file()
    {
        $testString = 'Foo is a buddy of bar';
        $fs = $this->newFilesystem();

        $fileName = sys_get_temp_dir() . '/' . basename(__FILE__) . '.tmp';

        $this->assertEquals(strlen($testString), $fs->write($fileName, $testString));
        $this->assertTrue(file_exists($fileName));
        $this->assertEquals($testString, $fs->contents($fileName));
        $fs->delete($fileName);
        $this->assertFalse($fs->exists($fileName));
    }

    public function test_delete_deletes_one_file()
    {
        $fs = $this->newFilesystem();
        $tempFile = $this->tempFile();

        $this->assertTrue($fs->exists($tempFile));
        $this->assertTrue($fs->delete($tempFile));
        $this->assertFalse($fs->exists($tempFile));
    }

    public function test_delete_deletes_many_files()
    {

        $fs = $this->newFilesystem();
        $count = 4;
        $tempFiles = array();

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

        $fs = $this->newFilesystem();

        $this->assertTrue(mkdir($dirName));
        $this->assertTrue($fs->exists($dirName));
        $this->assertTrue($fs->delete($dirName));
        $this->assertFalse($fs->exists($dirName));
    }

    public function test_delete_deletes_nested_directory()
    {
        $structure = array(
            'foo.txt' => '',
            'bar.txt' => '',
            'directory' => array(
                'baz.xml' => '',
                'users.json' => '',
                '2016' => array(
                    'gung.doc' => '',
                    'ho.odt' => ''
                )
            )
        );

        list($tempDir, $dirs) = $this->createNestedDirectories($structure);
        $fs = $this->newFilesystem();

        $this->assertTrue($fs->exists($tempDir));
        $this->assertTrue($fs->delete($tempDir));
        $this->assertFalse($fs->exists($tempDir));

    }

    public function test_list_directory_lists_paths()
    {

        $fs = $this->newFilesystem();
        $structure = array(
            'foo.txt' => '',
            'bar.txt' => '',
            'baz.txt' => ''
        );

        list($tmpDir, $dirs) = $this->createNestedDirectories($structure);
        $listedDirs = $fs->listDirectory($tmpDir);

        sort($listedDirs);
        sort($dirs);
        $this->assertEquals($dirs, $listedDirs);

    }

    public function test_list_directory_lists_path_recursive()
    {

        $fs = $this->newFilesystem();
        $structure = array(
            'foo.txt' => '',
            'bar.txt' => '',
            'directory' => array(
                'baz.xml' => '',
                'users.json' => '',
                '2016' => array(
                    'gung.doc' => '',
                    'ho.odt' => ''
                )
            )
        );

        list($tmpDir, $dirs) = $this->createNestedDirectories($structure);
        $listedDirs = $fs->listDirectory($tmpDir, true);

        sort($listedDirs);
        sort($dirs);

        $this->assertEquals($dirs, $listedDirs);

    }

    public function test_files_returns_only_files()
    {
        $fs = $this->newFilesystem();

        $structure = array(
            'foo.txt' => '',
            'bar.txt' => '',
            'baz.txt' => '',
            'directory' => array()
        );

        list($tmpDir, $dirs) = $this->createNestedDirectories($structure);

        $shouldBe = array_filter($dirs, function($path){
            return strpos($path, 'directory') === false;
        });

        sort($shouldBe);

        $files = $fs->files($tmpDir);

        sort($files);

        $this->assertEquals($shouldBe, $files);

    }

    public function test_files_returns_only_files_matching_pattern()
    {
        $fs = $this->newFilesystem();

        $structure = array(
            'foo.txt' => '',
            'bar.doc' => '',
            'baz.txt' => '',
            'directory' => array()
        );

        list($tmpDir, $dirs) = $this->createNestedDirectories($structure);

        $shouldBe = array_filter($dirs, function($path){
            return strpos($path, 'bar.doc') !== false;
        });

        sort($shouldBe);

        $files = $fs->files($tmpDir, '*.doc');

        sort($files);

        $this->assertEquals($shouldBe, $files);

    }

    public function test_files_returns_only_files_matching_extensions()
    {
        $fs = $this->newFilesystem();

        $structure = array(
            'foo.txt' => '',
            'bar.doc' => '',
            'baz.txt' => '',
            'hello.gif' => '',
            'bye.PNG' => '',
            'doc.doc.pdf' => ''
        );

        list($tmpDir, $dirs) = $this->createNestedDirectories($structure);

        $shouldBe = array_filter($dirs, function($path){
            return strpos($path, 'bar.doc') !== false;
        });

        sort($shouldBe);

        $files = $fs->files($tmpDir, '*', 'doc');

        sort($files);

        $this->assertEquals($shouldBe, $files);

        $shouldBe = array_filter($dirs, function($path){
            return strpos($path, 'hello.gif') !== false || strpos($path, 'bye.PNG') !== false;
        });

        sort($shouldBe);

        $files = $fs->files($tmpDir, '*', array('gif','png'));

        sort($files);

        $this->assertEquals($shouldBe, $files);

    }

    public function test_directories_returns_only_directories_matching_pattern()
    {
        $fs = $this->newFilesystem();

        $structure = array(
            'foo' => array(),
            'bar' => array(),
            'bar.txt' => '',
            'directory' => array(),
            'barely' => array()
        );

        list($tmpDir, $dirs) = $this->createNestedDirectories($structure);

        $shouldBe = array_filter($dirs, function($path){
            return strpos($path, 'bar') !== false && strpos($path, 'bar.txt') === false;
        });

        sort($shouldBe);

        $directories = $fs->directories($tmpDir, '*bar*');

        sort($directories);

        $this->assertEquals($shouldBe, $directories);

    }

    public function test_directories_returns_only_directories()
    {
        $fs = $this->newFilesystem();

        $structure = array(
            'foo' => array(),
            'bar' => array(),
            'baz.txt' => '',
            'directory' => array()
        );

        list($tmpDir, $dirs) = $this->createNestedDirectories($structure);

        $shouldBe = array_filter($dirs, function($path){
            return strpos($path, 'baz.txt') === false;
        });

        sort($shouldBe);

        $directories = $fs->directories($tmpDir);

        sort($directories);

        $this->assertEquals($shouldBe, $directories);


    }

    public function test_lastModified_returns_filemtime()
    {
        $fs = $this->newFilesystem();
        $this->assertEquals(filemtime(__FILE__), (int)$fs->lastModified(__FILE__)->format('U'));
    }

}
