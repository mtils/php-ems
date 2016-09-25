<?php

namespace Ems\Assets\Renderer;

use Ems\Assets\Registry;
use Ems\Core\LocalFilesystem;
use Ems\Core\ManualMimeTypeProvider;
use Ems\Assets\ExtensionAnalyser;
use Ems\Assets\AssetsFactoryMethods;

class CssRendererTest extends \Ems\TestCase
{

    use AssetsFactoryMethods;

    public function test_implements_interface()
    {
        $this->assertInstanceOf(
            'Ems\Contracts\Core\Renderer',
            $this->newRenderer('')
        );
    }

    public function test_renders_external_tags()
    {
        $url = 'http://localhost/css';

        $files = [
            'bootstrap.css',
            'layout.css',
            'typo.css'
        ];
        $renderer = $this->newRenderer();
        $collection = $this->newCollection($files);
        $result = $renderer->render($collection);

        foreach ($files as $file) {
            $this->assertContains("$url/$file", $result);
        }

        $this->assertEquals(3, substr_count($result, '<link'));
    }

    public function test_renders_inline_tags()
    {
        $url = 'http://localhost/css';

        $files = [
            'bootstrap.css',
            'layout.css',
            ['typo.css', 'table.search-result { width: 100%; }']
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

        $this->assertEquals(2, substr_count($result, '<link'));
        $this->assertEquals(2, substr_count($result, 'style>'));
    }

    protected function newRenderer()
    {
        return new CssRenderer;
    }

}
