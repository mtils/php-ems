<?php

namespace Ems\Assets\Parser;

use Ems\Testing\FilesystemMethods;
use RuntimeException;

class CssUrlReplaceParserTest extends \Ems\IntegrationTest
{
    use FilesystemMethods;

    public function test_implements_interface()
    {
        $this->assertInstanceOf(
            'Ems\Contracts\Core\TextParser',
            $this->newParser()
        );
    }

    public function test_parse_throws_exception_if_file_path_not_passed()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->newParser()->parse('foo', [
            'target_path' => '/tmp/out.css'
        ]);
    }

    public function test_parse_throws_exception_if_target_path_not_passed()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->newParser()->parse('foo', [
            'file_path' => '/tmp/out.css'
        ]);
    }

    public function test_parse_replaces_background_url()
    {
        $testLayout = [
            'lib' => [
                'infile.css' => ''
            ],
            'images' => [
                'blank.gif' => ''
            ],
            'outfile.css' => ''
        ];

        list($tempDir, $structure) = $this->createNestedDirectories($testLayout);

        $config = [
            'file_path'   => "$tempDir/lib/infile.css",
            'target_path' => "$tempDir/outfile.css",

        ];

        $testString = 'table { background: url(../images/blank.gif) }';

        $result = $this->newParser()->parse($testString, $config);

        $this->assertContains('url(images/blank.gif)', $result);
        $this->assertNotContains('url(../images/blank.gif)', $result);
    }

    public function test_parse_replaces_background_url_and_background_image()
    {
        $testLayout = [
            'lib' => [
                'infile.css' => ''
            ],
            'images' => [
                'blank.gif' => '',
                'bg.png'    => ''
            ],
            'outfile.css' => ''
        ];

        list($tempDir, $structure) = $this->createNestedDirectories($testLayout);

        $config = [
            'file_path'   => "$tempDir/lib/infile.css",
            'target_path' => "$tempDir/outfile.css",

        ];

        $testString = 'table {background: url(../images/blank.gif); background-image: url(../images/bg.png) 100% 100%;}';

        $result = $this->newParser()->parse($testString, $config);

        $this->assertContains('url(images/blank.gif)', $result);
        $this->assertNotContains('url(../images/blank.gif)', $result);
        $this->assertContains('url(images/bg.png)', $result);
        $this->assertNotContains('url(../images/bg.png)', $result);
    }

    public function test_parse_replaces_background_url_and_retains_other_directives()
    {
        $testLayout = [
            'lib' => [
                'infile.css' => ''
            ],
            'images' => [
                'blank.gif' => '',
                'bg.png'    => ''
            ],
            'outfile.css' => ''
        ];

        list($tempDir, $structure) = $this->createNestedDirectories($testLayout);

        $config = [
            'file_path'   => "$tempDir/lib/infile.css",
            'target_path' => "$tempDir/outfile.css",

        ];

        $testString = 'table {background: url(../images/blank.gif) 0px 60px repeat-x; background-image: url(../images/bg.png) 100% 100%;}';

        $result = $this->newParser()->parse($testString, $config);

        $this->assertContains('url(images/blank.gif)', $result);
        $this->assertContains('0px', $result);
        $this->assertContains('60px', $result);
        $this->assertContains('repeat-x', $result);
        $this->assertNotContains('url(../images/blank.gif)', $result);
        $this->assertContains('url(images/bg.png)', $result);
        $this->assertNotContains('url(../images/bg.png)', $result);
    }

    public function test_parse_throws_exception_if_referenced_file_does_not_exist()
    {
        $this->expectException(RuntimeException::class);
        $testLayout = [
            'lib' => [
                'infile.css' => ''
            ],
            'images' => [
                'bg.png' => ''
            ],
            'outfile.css' => ''
        ];

        list($tempDir, $structure) = $this->createNestedDirectories($testLayout);

        $config = [
            'file_path'   => "$tempDir/lib/infile.css",
            'target_path' => "$tempDir/outfile.css",

        ];

        $testString = 'table {background: url(../images/blank.gif);}';

        $result = $this->newParser()->parse($testString, $config);
    }

    public function test_parse_does_not_replace_absolute_urls()
    {
        $testLayout = [
            'lib' => [
                'infile.css' => ''
            ],
            'images' => [
                'blank.gif' => '',
                'bg.png'    => ''
            ],
            'outfile.css' => ''
        ];

        list($tempDir, $structure) = $this->createNestedDirectories($testLayout);

        $config = [
            'file_path'   => "$tempDir/lib/infile.css",
            'target_path' => "$tempDir/outfile.css",

        ];

        $testString = 'table {background: url(http://localhost/images/blank.gif);';

        $result = $this->newParser()->parse($testString, $config);

        $this->assertContains('url(http://localhost/images/blank.gif)', $result);
        $this->assertNotContains('url(images/blank.gif)', $result);

        $testString = 'table {background: url(https://localhost/images/blank.gif);';

        $result = $this->newParser()->parse($testString, $config);

        $this->assertContains('url(https://localhost/images/blank.gif)', $result);
        $this->assertNotContains('url(images/blank.gif)', $result);

        $testString = 'table {background: url(//images/blank.gif);';

        $result = $this->newParser()->parse($testString, $config);

        $this->assertContains('url(//images/blank.gif)', $result);
        $this->assertNotContains('url(images/blank.gif)', $result);


        $testString = 'table {background: url(/images/blank.gif);';

        $result = $this->newParser()->parse($testString, $config);

        $this->assertContains('url(/images/blank.gif)', $result);
        $this->assertNotContains('url(images/blank.gif)', $result);
    }

    public function test_parse_does_not_replace_data_urls()
    {
        $testLayout = [
            'lib' => [
                'infile.css' => ''
            ],
            'images' => [
                'blank.gif' => '',
                'bg.png'    => ''
            ],
            'outfile.css' => ''
        ];

        list($tempDir, $structure) = $this->createNestedDirectories($testLayout);

        $config = [
            'file_path'   => "$tempDir/lib/infile.css",
            'target_path' => "$tempDir/outfile.css",

        ];

        $testString = 'table {background: url(data:foo);';

        $result = $this->newParser()->parse($testString, $config);

        $this->assertContains('url(data:foo)', $result);
    }

    public function test_parse_replaces_relative_paths_without_dots()
    {
        $testLayout = [
            'lib' => [
                'infile.css' => '',
                'blank.gif'  => ''
            ],
            'outfile.css' => ''
        ];

        list($tempDir, $structure) = $this->createNestedDirectories($testLayout);

        $config = [
            'file_path'   => "$tempDir/lib/infile.css",
            'target_path' => "$tempDir/outfile.css",

        ];

        $testString = 'table {background: url(blank.gif);}';

        $result = $this->newParser()->parse($testString, $config);

        $this->assertContains('url(lib/blank.gif)', $result);
        $this->assertNotContains('url(blank.gif)', $result);
    }

    public function test_parse_replaces_relative_paths_with_tick_marks()
    {
        $testLayout = [
            'lib' => [
                'infile.css' => '',
                'blank.gif'  => ''
            ],
            'outfile.css' => ''
        ];

        list($tempDir, $structure) = $this->createNestedDirectories($testLayout);

        $config = [
            'file_path'   => "$tempDir/lib/infile.css",
            'target_path' => "$tempDir/outfile.css",

        ];

        $testString = 'table {background: url("blank.gif");}';

        $result = $this->newParser()->parse($testString, $config);

        $this->assertContains('url("lib/blank.gif")', $result);
        $this->assertNotContains('url("blank.gif")', $result);

        $testString = 'table {background: url(\'blank.gif\');}';

        $result = $this->newParser()->parse($testString, $config);

        $this->assertContains('url(\'lib/blank.gif\')', $result);
        $this->assertNotContains('url(\'blank.gif\')', $result);
    }

    public function test_parse_replaces_relative_paths_in_deeper_hierarchy()
    {
        $testLayout = [
            'plugins' => [
                'jquery-ui' => [
                    'jquery-ui.css' => '',
                    'images'        => [
                        'bullet.gif' => ''
                    ]
                ]
            ],
            'outfile.css' => ''
        ];

        list($tempDir, $structure) = $this->createNestedDirectories($testLayout);

        $config = [
            'file_path'   => "$tempDir/plugins/jquery-ui/jquery-ui.css",
            'target_path' => "$tempDir/outfile.css",

        ];

        $testString = 'table {background: url(images/bullet.gif);}';

        $result = $this->newParser()->parse($testString, $config);

        $this->assertContains('url(plugins/jquery-ui/images/bullet.gif)', $result);
        $this->assertNotContains('url(images/bullet.gif)', $result);
    }


    protected function newParser()
    {
        return new CssUrlReplaceParser($this->newFilesystem());
    }
}

