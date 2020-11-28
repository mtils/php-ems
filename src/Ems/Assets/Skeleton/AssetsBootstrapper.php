<?php

namespace Ems\Assets\Skeleton;

use Ems\Core\Skeleton\Bootstrapper;
use Ems\Contracts\Assets\Registry;
use Ems\Core\Support\RendererChain;
use Ems\Assets\Laravel\AssetsBladeDirectives;
use Illuminate\Contracts\Routing\Registrar;
use Ems\Contracts\Core\PathFinder;

class AssetsBootstrapper extends Bootstrapper
{
    protected $singletons = [
        'Ems\Assets\ExtensionAnalyser'     => ['Ems\Contracts\Assets\NameAnalyser'],
        'Ems\Assets\BuildConfigRepository' => ['Ems\Contracts\Assets\BuildConfigRepository'],
        'Ems\Assets\Builder'               => ['Ems\Contracts\Assets\Builder'],
        'Ems\Assets\Compiler'              => ['Ems\Contracts\Assets\Compiler'],
        'Ems\Assets\Registry'              => ['Ems\Contracts\Assets\Registry', 'Ems\Contracts\Assets\Registrar', 'ems.assets.registry'],
        'Ems\Assets\Manager'               => ['Ems\Contracts\Assets\Manager', 'ems.assets'],
    ];

    protected $bindings = [
        'Ems\Assets\Asset'      => 'Ems\Contracts\Assets\Asset',
        'Ems\Assets\Collection' => 'Ems\Contracts\Assets\Collection',
    ];

    public function bind()
    {
        parent::bind();

        $this->registerViewExtensions();

        $this->app->on('Ems\Core\Support\RendererChain', function ($chain) {
            $this->addRenderers($chain);
        });

        $this->app->on('Ems\Contracts\Assets\Registry', function ($factory) {
            $this->registerPathMappings($factory);
        });

        $this->app->on('Ems\Assets\Manager', function ($manager, $app) {
            $manager->setBuildConfigRepository($app('Ems\Contracts\Assets\BuildConfigRepository'));
        });

        $this->app->onAfter('Illuminate\Contracts\View\Factory', function ($factory, $app) {
             AssetsBladeDirectives::injectOriginalViewData($factory);
        });

        $this->app->onAfter('blade.compiler', function ($compiler, $app) {
            $app('Ems\Assets\Laravel\AssetsBladeDirectives')->registerDirectives($compiler);
        });

        $this->app->onAfter('Illuminate\Contracts\Routing\Registrar', function ($router, $app) {
            $this->addRoutes($router);
        });

        $this->app->on('Ems\Assets\Compiler', function ($compiler) {
            $this->addInstalledParsers($compiler);
        });

//         $this->commands([
//             'Ems\Assets\Symfony\ListBuildConfigurationsCommand',
//             'Ems\Assets\Symfony\CompileCommand',
//         ]);
    }

    protected function registerViewExtensions()
    {
        $this->app->bind('Ems\Assets\Laravel\AssetsBladeDirectives', function ($app) {
            return new AssetsBladeDirectives($app('Ems\Contracts\Assets\Manager'));
        }, true);
    }

    protected function addRenderers(RendererChain $chain)
    {
        $this->addCssRenderer($chain);
        $this->addJsRenderer($chain);
    }

    /**
     * This method assumes that the CoreBootstrapper already has assigned
     * a "assets" path and url.
     *
     * @param \Ems\Contracts\Assets\Registry
     **/
    protected function registerPathMappings($factory)
    {
        $assetPath = $this->app->__invoke(PathFinder::class)->to('assets');

        $factory->map('css', $assetPath->absolute('css'), $assetPath->url('css'));
        $factory->map('js', $assetPath->absolute('js'), $assetPath->url('js'));
    }

    protected function addCssRenderer(RendererChain $chain)
    {
        $chain->add($this->app->__invoke('Ems\Assets\Renderer\CssRenderer'));
    }

    protected function addJsRenderer(RendererChain $chain)
    {
        $chain->add($this->app->__invoke('Ems\Assets\Renderer\JavascriptRenderer'));
    }

    protected function addRoutes(Registrar $router)
    {
        $router->get('_ems/asset', [
            'uses' => '\Ems\Assets\Laravel\AssetController@show',
            'as'   => '_ems-assets.show',
        ]);
    }

    protected function addInstalledParsers($compiler)
    {
        if (class_exists('CssMin')) {
            $compiler->addParser('cssmin/cssmin', $this->app->__invoke('Ems\Assets\Parser\CssMinParser'));
            $compiler->addParser('ems/css-url-replace', $this->app->__invoke('Ems\Assets\Parser\CssUrlReplaceParser'));
        }

        if (class_exists('Patchwork\JSqueeze')) {
            $compiler->addParser('patchwork/jsqueeze', $this->app->__invoke('Ems\Assets\Parser\JSqueezeParser'));
        }

        if (class_exists('JShrink\Minifier')) {
            $compiler->addParser('tedivm/jshrink', $this->app->__invoke('Ems\Assets\Parser\JShrinkParser'));
        }
    }

    /**
     * TODO: Implement URL Dispatcher.
     **/
    protected function publicPath($subPath = '')
    {
        if (function_exists('public_path')) {
            return public_path($subPath);
        }

        $basePath = $this->app->__invoke('app')->path()->append('public');

        return $subPath ? "$basePath/$subPath" : $basePath;
    }

    protected function url($path = '')
    {
        if (function_exists('url')) {
            return url($path);
        }

        $url = $this->app->__invoke('app')->url();

        return $path ? "$url/$path" : $url;
    }
}
