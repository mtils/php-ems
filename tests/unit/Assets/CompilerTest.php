<?php


namespace Ems\Assets;

use Ems\Core\LocalFilesystem;
use Ems\Core\ManualMimeTypeProvider;
use Ems\Contracts\Assets\NameAnalyser;
use Ems\Contracts\Assets\Registry as RegistryContract;
use Ems\Testing\LoggingCallable;
use Ems\Testing\Cheat;
use Ems\Contracts\Core\Filesystem;

class CompilerTest extends \Ems\TestCase
{

    use AssetsFactoryMethods;

    public function test_implements_interface()
    {
        $this->assertInstanceOf(
            'Ems\Contracts\Assets\Compiler',
            $this->newCompiler()
        );
    }

    public function test_addParser_returns_compiler()
    {
        $compiler = $this->newCompiler();
        $parser = $this->mockParser();
        $this->assertSame($compiler, $compiler->addParser('test', $parser));
    }

    public function test_addParser_adds_parser_and_returns_it_by_parser()
    {
        $compiler = $this->newCompiler();
        $parser = $this->mockParser();
        $compiler->addParser('test', $parser);
        $this->assertSame($parser, $compiler->parser('test'));

    }

    /**
     * @expectedException \Ems\Contracts\Core\NotFound
     **/
    public function test_parser_throws_exception_if_not_found()
    {
        $compiler = $this->newCompiler();
        $parser = $this->mockParser();
        $compiler->addParser('test', $parser);
        $compiler->parser('testi');

    }

    public function test_removeParser_removes_parser()
    {
        $compiler = $this->newCompiler();
        $parser = $this->mockParser();
        $compiler->addParser('test', $parser);
        $this->assertSame($parser, $compiler->parser('test'));
        $this->assertSame($compiler, $compiler->removeParser('test'));

        try {
            $compiler->parser('test');
            $this->fail('Removed parser is still in compiler');
        } catch(\Ems\Contracts\Core\NotFound $e) {

        }

    }

    public function test_parserNames_returns_assigned_parserNames()
    {
        $compiler = $this->newCompiler();
        $parser = $this->mockParser();
        $compiler->addParser('test', $parser);
        $this->assertEquals(['test'], $compiler->parserNames());
        $compiler->addParser('test2', $parser);
        $this->assertEquals(['test','test2'], $compiler->parserNames());
        $compiler->removeParser('test');
        $this->assertEquals(['test2'], $compiler->parserNames());

    }

    public function test_compile_collects_file_contents()
    {

        $registry = $this->mockRegistry();
        $files = $this->mockFilesystem();

        $compiler = $this->newCompiler($files, $registry);

        $assets = [
            'reset.css',
            'layout.css',
            'typo.css'
        ];

        $collection = $this->newCollection($assets, 'css');

        $pathProvider = function ($file) {
            return "/var/www/$file";
        };

        $registry->shouldReceive('to')
                 ->andReturn($registry);

        $registry->shouldReceive('absolute')
                 ->andReturnUsing($pathProvider);
        $files->shouldReceive('contents')
                      ->andReturnUsing(function ($file) {
                        return $file;
                      });

        $awaited = $this->contentFromFiles($pathProvider, $assets);

        $this->assertEquals($awaited,  $compiler->compile($collection));

    }

    public function test_compile_calls_passed_parsers()
    {

        $registry = $this->mockRegistry();
        $files = $this->mockFilesystem();

        $compiler = $this->newCompiler($files, $registry);

        $assets = [
            'reset.css',
//             'layout.css',
//             'typo.css'
        ];

        $collection = $this->newCollection($assets, 'css');

        $pathProvider = function ($file) {
            return "/var/www/$file";
        };

        $registry->shouldReceive('to')
                 ->andReturn($registry);

        $registry->shouldReceive('absolute')
                 ->andReturnUsing($pathProvider);
        $files->shouldReceive('contents')
                      ->andReturnUsing(function ($file) {
                        return $file;
                      });

        $parser1 = $this->mockParser();
        $compiler->addParser('test1', $parser1);

        $parser2 = $this->mockParser();
        $compiler->addParser('test2', $parser2);

        $parserOptions = [
            'collection' => $collection,
            'parser_name' => 'test1',
            'asset' => $collection[$this->index($collection,'reset.css')],
            'file_path' => '/var/www/reset.css'
        ];

        $parserOptions2 = [
            'collection' => $collection,
            'parser_name' => 'test2',
            'asset' => $parserOptions['asset'],
            'file_path' => $parserOptions['file_path']
        ];


        $contents = $this->contentFromFiles($pathProvider, $assets);

        $parser1->shouldReceive('parse')
                ->with('/var/www/reset.css', $parserOptions, false)
                ->once()
                ->andReturn('test1-parsed');

        $parser1->shouldReceive('purge')
                ->with('test2-parsed')
                ->andReturn('test2-parsed')
                ->once();

        $parser2->shouldReceive('parse')
                ->with('test1-parsed', $parserOptions2, false)
                ->once()
                ->andReturn('test2-parsed');

        $parser2->shouldReceive('purge')
                ->with('test2-parsed')
                ->andReturn('test2-parsed')
                ->once();


        $this->assertEquals('test2-parsed',  $compiler->compile($collection, ['test1', 'test2']));

    }

    public function test_compile_calls_passed_parsers_with_parser_options()
    {

        $registry = $this->mockRegistry();
        $files = $this->mockFilesystem();

        $compiler = $this->newCompiler($files, $registry);

        $assets = [
            'reset.css',
//             'layout.css',
//             'typo.css'
        ];

        $collection = $this->newCollection($assets, 'css');

        $pathProvider = function ($file) {
            return "/var/www/$file";
        };

        $registry->shouldReceive('to')
                 ->andReturn($registry);

        $registry->shouldReceive('absolute')
                 ->andReturnUsing($pathProvider);
        $files->shouldReceive('contents')
                      ->andReturnUsing(function ($file) {
                        return $file;
                      });

        $parser1 = $this->mockParser();
        $compiler->addParser('test1', $parser1);

        $parser2 = $this->mockParser();
        $compiler->addParser('test2', $parser2);


        $contents = $this->contentFromFiles($pathProvider, $assets);

        $parserOptions = [
            'test1' => [
                'allow-html' => true
            ]
        ];

        $parserOptions1 = [
            'collection' => $collection,
            'parser_name' => 'test1',
            'asset' => $collection[$this->index($collection,'reset.css')],
            'file_path' => '/var/www/reset.css',
            'allow-html' => true
        ];

        $parserOptions2 = [
            'collection' => $collection,
            'parser_name' => 'test2',
            'asset' => $parserOptions1['asset'],
            'file_path' => $parserOptions1['file_path']
        ];

        $parser1->shouldReceive('parse')
                ->with('/var/www/reset.css', $parserOptions1, false)
                ->once()
                ->andReturn('test1-parsed');

        $parser1->shouldReceive('purge')
                ->with('test2-parsed')
                ->andReturn('test2-parsed')
                ->once();

        $parser2->shouldReceive('parse')
                ->with('test1-parsed', $parserOptions2, false)
                ->once()
                ->andReturn('test2-parsed');

        $parser2->shouldReceive('purge')
                ->with('test2-parsed')
                ->andReturn('test2-parsed')
                ->once();

        

        $this->assertEquals('test2-parsed',  $compiler->compile($collection, ['test1', 'test2'], $parserOptions));

    }
    
    public function test_compile_calls_passed_parsers_with_parser_options_assigned_with_wildcard()
    {

        $registry = $this->mockRegistry();
        $files = $this->mockFilesystem();

        $compiler = $this->newCompiler($files, $registry);

        $assets = [
            'reset.css',
//             'layout.css',
//             'typo.css'
        ];

        $collection = $this->newCollection($assets, 'css');

        $pathProvider = function ($file) {
            return "/var/www/$file";
        };

        $registry->shouldReceive('to')
                 ->andReturn($registry);

        $registry->shouldReceive('absolute')
                 ->andReturnUsing($pathProvider);
        $files->shouldReceive('contents')
                      ->andReturnUsing(function ($file) {
                        return $file;
                      });

        $parser1 = $this->mockParser();
        $compiler->addParser('test1', $parser1);

        $parser2 = $this->mockParser();
        $compiler->addParser('test2', $parser2);


        $contents = $this->contentFromFiles($pathProvider, $assets);

        $parserOptions = [
            '*' => [
                'allow-html' => true
            ]
        ];

        $parserOptions1 = [
            'collection' => $collection,
            'parser_name' => 'test1',
            'asset' => $collection[$this->index($collection,'reset.css')],
            'file_path' => '/var/www/reset.css',
            'allow-html' => true
        ];

        $parserOptions2 = [
            'collection' => $collection,
            'parser_name' => 'test2',
            'asset' => $parserOptions1['asset'],
            'file_path' => $parserOptions1['file_path'],
            'allow-html' => true
        ];

        $parser1->shouldReceive('parse')
                ->with('/var/www/reset.css', $parserOptions1, false)
                ->once()
                ->andReturn('test1-parsed');

        $parser1->shouldReceive('purge')
                ->with('test2-parsed')
                ->andReturn('test2-parsed')
                ->once();

        $parser2->shouldReceive('parse')
                ->with('test1-parsed', $parserOptions2, false)
                ->once()
                ->andReturn('test2-parsed');

        $parser2->shouldReceive('purge')
                ->with('test2-parsed')
                ->andReturn('test2-parsed')
                ->once();

        

        $this->assertEquals('test2-parsed',  $compiler->compile($collection, ['test1', 'test2'], $parserOptions));

    }

    public function test_compile_calls_listener()
    {

        $registry = $this->mockRegistry();
        $files = $this->mockFilesystem();

        $compiler = $this->newCompiler($files, $registry);

        $listener = new LoggingCallable;

        $compiler->whenCompiled($listener);

        $assets = [
            'reset.css',
            'layout.css',
            'typo.css'
        ];

        $collection = $this->newCollection($assets, 'css');

        $pathProvider = function ($file) {
            return "/var/www/$file";
        };

        $registry->shouldReceive('to')
                 ->andReturn($registry);

        $registry->shouldReceive('absolute')
                 ->andReturnUsing($pathProvider);
        $files->shouldReceive('contents')
                      ->andReturnUsing(function ($file) {
                        return $file;
                      });

        $awaited = $this->contentFromFiles($pathProvider, $assets);

        $this->assertEquals($awaited,  $compiler->compile($collection));

        $this->assertSame($collection, $listener->arg(0));
        $this->assertEquals($awaited, $listener->arg(1));
        $this->assertEquals([], $listener->arg(2));
        $this->assertEquals([], $listener->arg(3));
        $this->assertCount(1, $listener);

    }

    public function test_compile_uses_listener_output_if_it_returned_an_nonempty_string()
    {

        $registry = $this->mockRegistry();
        $files = $this->mockFilesystem();

        $compiler = $this->newCompiler($files, $registry);

        $compiler->whenCompiled(function($collection, $content) {
            return 'foo';
        });

        $assets = [
            'reset.css',
            'layout.css',
            'typo.css'
        ];

        $collection = $this->newCollection($assets, 'css');

        $pathProvider = function ($file) {
            return "/var/www/$file";
        };

        $registry->shouldReceive('to')
                 ->andReturn($registry);

        $registry->shouldReceive('absolute')
                 ->andReturnUsing($pathProvider);
        $files->shouldReceive('contents')
                      ->andReturnUsing(function ($file) {
                        return $file;
                      });

        $awaited = $this->contentFromFiles($pathProvider, $assets);

        $this->assertEquals('foo',  $compiler->compile($collection));


    }

    public function test_compile_uses_original_output_if_it_returned_a_trueish_nonstring()
    {

        $registry = $this->mockRegistry();
        $files = $this->mockFilesystem();

        $compiler = $this->newCompiler($files, $registry);

        $compiler->whenCompiled(function($collection, $content) {
            return true;
        });

        $assets = [
            'reset.css',
            'layout.css',
            'typo.css'
        ];

        $collection = $this->newCollection($assets, 'css');

        $pathProvider = function ($file) {
            return "/var/www/$file";
        };

        $registry->shouldReceive('to')
                 ->andReturn($registry);

        $registry->shouldReceive('absolute')
                 ->andReturnUsing($pathProvider);
        $files->shouldReceive('contents')
                      ->andReturnUsing(function ($file) {
                        return $file;
                      });

        $awaited = $this->contentFromFiles($pathProvider, $assets);

        $this->assertEquals($awaited,  $compiler->compile($collection));


    }

    protected function newCompiler(FileSystem $fileSystem=null, RegistryContract $registry=null)
    {
        $fileSystem = $fileSystem ?: $this->newFilesystem();
        $registry = $registry ?: $this->newRegistry();
        return new Compiler($fileSystem, $registry);
    }

    protected function newCollection($files, $group, $mimeType='text/css')
    {
        $collection = new Collection;
        $collection->setGroup($group);
        foreach ((array)$files as $file) {
            $collection->append($this->newAsset($file, $group, $mimeType));
        }
        return $collection;
    }

    protected function newAsset($name, $group, $mimeType='text/css')
    {
        return (new Asset())
                ->setName($name)
                ->setGroup($group)
                ->setMimeType($mimeType);
    }

    protected function newFilesystem()
    {
        return new LocalFilesystem;
    }

    protected function mockFilesystem()
    {
        return $this->mock('Ems\Contracts\Core\Filesystem');
    }

    protected function mockRegistry()
    {
        return $this->mock('Ems\Contracts\Assets\Registry');
    }

    protected function mockParser()
    {
        return $this->mock('Ems\Contracts\Core\TextParser');
    }

    protected function contentFromFiles(callable $contentsProvider, array $assets)
    {
        $contents = '';
        $nl = '';
        foreach ($assets as $asset) {
            $contents .= $nl . $contentsProvider($asset);
            $nl = "\n";
        }
        return $contents;
    }
}
