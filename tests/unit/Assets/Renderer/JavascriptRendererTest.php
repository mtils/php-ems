<?php

namespace Ems\Assets\Renderer;

use Ems\Assets\Registry;
use Ems\Core\LocalFilesystem;
use Ems\Core\ManualMimeTypeProvider;
use Ems\Assets\ExtensionAnalyser;
use Ems\Assets\AssetsFactoryMethods;

class JavascriptRendererTest extends \Ems\TestCase
{
    use AssetsFactoryMethods;

    public function test_implements_interface()
    {
        $this->assertInstanceOf(
            'Ems\Contracts\Core\Renderer',
            $this->newRenderer()
        );
    }

    public function test_renders_external_tags()
    {
        $url = 'http://localhost/js';

        $files = [
            'jquery.js',
            'jquery.contextMenu.js',
            'jquery.select2.js'
        ];
        $renderer = $this->newRenderer();
        $collection = $this->newCollection($files);
        $result = $renderer->render($collection);

        foreach ($files as $file) {
            $this->assertContains("$url/$file", $result);
        }

        $this->assertEquals(3, substr_count($result, '<script'));
    }

    public function test_renders_inline_tags()
    {
        $url = 'http://localhost/js';

        $files = [
            'jquery.js',
            'jquery.contextMenu.js',
            ['inline.js', 'alert(\'Guguck\')']
        ];

        $renderer = $this->newRenderer();
        $collection = $this->newCollection($files);
        $result = $renderer->render($collection);

        foreach ($files as $file) {
            if (is_array($file)) {
                $this->assertContains($file[1], $result);
                continue;
            }
            $this->assertContains("$url/$file", $result);
        }

        $this->assertEquals(3, substr_count($result, '<script'));
        $this->assertEquals(3, substr_count($result, '</script>'));
    }

    protected function newRenderer()
    {
        return new JavascriptRenderer();
    }
}
