<?php


namespace Ems\Assets;

use Ems\Contracts\Core\Filesystem;
use Ems\Contracts\Core\MimeTypeProvider;
use Ems\Core\LocalFilesystem;
use Ems\Core\ManualMimeTypeProvider;
use Ems\Contracts\Assets\NameAnalyser;
use Ems\Core\PathFinder;
use Ems\Contracts\Assets\Registry as RegistryContract;
use Ems\Contracts\Core\Renderer;
use Ems\Core\Support\RendererChain;


trait AssetsFactoryMethods
{

    protected function newCollection($assets)
    {

        $registry = $this->newRegistry();

        foreach ((array)$assets as $asset) {
            if (!is_array($asset)) {
                $registry->import($asset);
                continue;
            }
            $registry->inline($asset[0], $asset[1]);
        }

        $groups = $registry->groups();

        return $registry[$groups[0]];
    }

    protected function all(RegistryContract $registry)
    {
        $all = [];
        foreach ($registry as $group=>$collection) {
            $all[$group] = $collection;
        }
        return $all;
    }

    protected function index(Collection $collection, $name)
    {
        return $collection->findIndex(function ($asset) use ($name) {
            return $name == $asset->name();
        });
    }

    protected function assetNames(Collection $collection)
    {
        return $collection->apply(function ($asset) {
            return $asset->name();
        });
    }

    protected function assetUris(Collection $collection)
    {
        return $collection->apply(function ($asset) {
            return $asset->uri();
        });
    }

    protected function newManager(RegistryContract $registry=null, Renderer $renderer=null, NameAnalyser $namer=null)
    {
        $registry = $registry ?: $this->newRegistry();
        $renderer = $renderer ?: $this->newRenderer();
        $namer = $namer ?: $this->newNamer();

        return new Manager($registry, $renderer, $namer);
    }

    protected function newRegistry(NameAnalyser $namer=null)
    {
        $namer = $namer ?: $this->newNamer();
        return new Registry($namer, $this->newPathFinder());
    }

    protected function newNamer(Filesystem $files=null, MimeTypeProvider $mimeTypes=null )
    {
        $files = $files ?: new LocalFilesystem;
        $mimeTypes = $mimeTypes ?: new ManualMimeTypeProvider;
        return new ExtensionAnalyser($files, $mimeTypes);
    }

    protected function newRenderer()
    {
        return new RendererChain();
    }

    protected function mockRegistry()
    {
        return $this->mock('Ems\Contracts\Assets\Registry');
    }

    protected function mockRenderer()
    {
        return $this->mock('Ems\Contracts\Assets\Renderer');
    }

    protected function newAsset($name, $group=null, $content=null)
    {
        return $this->newRegistry()->newAsset($name, $group, $content);
    }

    protected function newPathFinder()
    {
        $finder = new PathFinder;

        $finder->map('assets::js', '/srv/js', 'http://localhost/js');
        $finder->map('assets::css', '/srv/css', 'http://localhost/css');
        $finder->map('assets::base', '/srv/base', 'http://localhost/base');
        $finder->map('assets::less', '/srv/less', 'http://localhost/less');
        return $finder;
    }

    protected function newBuildConfigRepository(array $configs = [])
    {
        $repo = new BuildConfigRepository($this->newRegistry(), new LocalFilesystem);

        foreach ($configs as $config) {
            $repo->store($config);
        }

        return $repo;
    }
}
