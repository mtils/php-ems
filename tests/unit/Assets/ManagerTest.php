<?php


namespace Ems\Assets;

use Ems\Core\LocalFilesystem;
use Ems\Core\ManualMimeTypeProvider;
use Ems\Contracts\Assets\NameAnalyser;
use Ems\Contracts\Assets\Registry as RegistryContract;
use Ems\Testing\LoggingCallable;
use Ems\Testing\Cheat;

class ManagerTest extends \Ems\TestCase
{

    use AssetsFactoryMethods;

    public function test_implements_interface()
    {
        $this->assertInstanceOf(
            'Ems\Contracts\Assets\Manager',
            $this->newManager()
        );
    }

    public function test_import_calls_registry_and_returns_manager()
    {
        $registry = $this->mockRegistry();
        $manager = $this->newManager($registry);
        $asset = 'asset.js';
        $group = 'assets';


        $registry->shouldReceive('import')
                 ->with($asset, $group)
                 ->once();

        $this->assertSame($manager, $manager->import($asset, $group));
    }

    public function test_inline_calls_registry_and_returns_manager()
    {
        $registry = $this->mockRegistry();
        $manager = $this->newManager($registry);
        $asset = 'asset.js';
        $group = 'assets';
        $content = 'content';


        $registry->shouldReceive('inline')
                 ->with($asset, $content, $group)
                 ->once();

        $this->assertSame($manager, $manager->inline($asset, $content, $group));
    }

    public function test_newAsset_forwards_to_registry()
    {
        $registry = $this->mockRegistry();
        $manager = $this->newManager($registry);
        $asset = 'asset.js';
        $group = 'js';
        $content = 'content';

        $result = $this->newAsset($asset, $group, $content);


        $registry->shouldReceive('newAsset')
                 ->with($asset, $content, $group)
                 ->once()
                 ->andReturn($result);

        $this->assertSame($result, $manager->newAsset($asset, $content, $group));
    }

    public function test_on_forwards_to_registry()
    {
        $registry = $this->mockRegistry();
        $manager = $this->newManager($registry);
        $asset = 'asset.js';
        $handler = function(){};

        $registry->shouldReceive('on')
                 ->with($asset, $handler)
                 ->once();

        $this->assertSame($manager, $manager->on($asset, $handler));
    }

    public function test_after_forwards_to_registry()
    {
        $registry = $this->mockRegistry();
        $manager = $this->newManager($registry);
        $asset = 'asset.js';

        $registry->shouldReceive('after')
                 ->with($asset)
                 ->once();

        $this->assertSame($manager, $manager->after($asset));
    }

    public function test_before_forwards_to_registry()
    {
        $registry = $this->mockRegistry();
        $manager = $this->newManager($registry);
        $asset = 'asset.js';

        $registry->shouldReceive('before')
                 ->with($asset)
                 ->once();

        $this->assertSame($manager, $manager->before($asset));
    }

    public function test_render_forwards_to_custom_handler_if_assigned()
    {
        $registry = $this->newRegistry();
        $manager = $this->newManager($registry);

        $group = 'js';

        $listener = new LoggingCallable(function($group, $registry){
            return 'hello';
        });

        $manager->renderGroupWith('js', $listener);

        $this->assertEquals('hello', $manager->render($group));

        $this->assertEquals($group, $listener->arg(0));
        $this->assertSame($registry, $listener->arg(1));

    }

    public function test_render_assigns_renderer_to_collection()
    {
        $registry = $this->newRegistry();
        $renderer = $this->newRenderer();
        $manager = $this->newManager($registry, $renderer);

        $asset = 'asset.js';
        $group = 'js';

        $manager->import($asset);

        $this->assertSame($renderer, $manager->render($group)->getRenderer());

    }

    public function test_compiled_asset_replaces_collection_assets()
    {

        $compiledFiles = ['reset.css', 'layout.css', 'typo.css'];
        $importedFiles = ['reset.css', 'layout.css', 'typo.css','fixes.css'];

        $buildConfig = [
            'group' => 'css',
            'target' => 'app.css',
            'files' => $compiledFiles,
            'managerOptions' => [Manager::CHECK_COMPILED_FILE_EXISTS=>false]
        ];

        $buildRepo = $this->newBuildConfigRepository([$buildConfig]);
        $manager = $this->newManager();

        $manager->setBuildConfigRepository($buildRepo);

        $this->assertSame($buildRepo, $manager->getBuildConfigRepository());

        foreach ($importedFiles as $file) {
            $manager->import($file);
        }

        $collection = $manager->render('css');

        $awaited = ['app.css', 'fixes.css'];
        $awaitedUris = ['http://localhost/css/app.css', 'http://localhost/css/fixes.css'];

        $this->assertEquals($awaited, $this->assetNames($collection));
        $this->assertEquals($awaitedUris, $this->assetUris($collection));


    }

    public function test_not_found_BuildConfig_leads_to_normal_rendering()
    {

        $compiledFiles = ['reset.css', 'layout.css', 'typo.css'];
        $importedFiles = ['reset.css', 'layout.css', 'typo.css','fixes.css'];

        $buildConfig = [
            'group' => 'js',
            'target' => 'app.js',
            'files' => ['some.js']
        ];

        $buildRepo = $this->newBuildConfigRepository([$buildConfig]);
        $manager = $this->newManager();

        $manager->setBuildConfigRepository($buildRepo);

        $this->assertSame($buildRepo, $manager->getBuildConfigRepository());

        foreach ($importedFiles as $file) {
            $manager->import($file);
        }

        $collection = $manager->render('css');

        $this->assertEquals($importedFiles, $this->assetNames($collection));


    }

    public function test_replicate_returns_prefixed_manager()
    {
        $registry = $this->newRegistry();
        $renderer = $this->newRenderer();
        $manager = $this->newManager($registry, $renderer);

        $group = 'ems';

        $copy = $manager->replicate(['groupPrefix'=>$group]);

        $this->assertInstanceOf('Ems\Contracts\Assets\Manager', $copy);
        $this->assertNotSame($manager, $copy);
        $this->assertEquals($group, $copy->groupPrefix());

    }

}
