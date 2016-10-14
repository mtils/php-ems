<?php


namespace Ems\Core\Skeleton;

use Ems\Contracts\Core\PathFinder;
use Ems\Core\StringConverterChain;
use Ems\Core\StringConverter\MBStringConverter;
use Ems\Core\StringConverter\IconvStringConverter;
use Ems\Core\StringConverter\AsciiStringConverter;


class CoreBootstrapper extends Bootstrapper
{

    protected $singletons = [
        'Ems\Core\LocalFilesystem'          => 'Ems\Contracts\Core\Filesystem',
        'Ems\Core\ManualMimeTypeProvider'   => 'Ems\Contracts\Core\MimeTypeProvider',
        'Ems\Core\Support\RendererChain'    => 'Ems\Contracts\Core\Renderer',
        'Ems\Core\PathFinder'               => 'Ems\Contracts\Core\PathFinder',
        'Ems\Core\InputCorrector'           => 'Ems\Contracts\Core\InputCorrector',
        'Ems\Core\InputCaster'              => 'Ems\Contracts\Core\InputCaster',
        'Ems\Core\StringConverterChain'     => 'Ems\Contracts\Core\StringConverter'
    ];

    protected $bindings = [
        'Ems\Core\AppPath'                  => 'Ems\Contracts\Core\AppPath',
    ];

    public function bind()
    {

        parent::bind();

        $this->app->resolving(PathFinder::class, function($paths){
            $this->assignBaseAppPaths($paths);
        });

        $this->app->resolving(StringConverterChain::class, function($chain){
            $this->addStringConverters($chain);
        });

    }

    protected function assignBaseAppPaths(PathFinder $paths)
    {
        $paths->map('app', $this->appPath(), $this->url());
        $paths->map('assets', $this->publicPath(), $this->url());
    }

    protected function addStringConverters(StringConverterChain $chain)
    {
        $classes = [
            IconvStringConverter::class,
            MBStringConverter::class,
            AsciiStringConverter::class
        ];

        foreach ($classes as $class) {
            try {
                $chain->add($this->app->make($class));
            } catch (\RuntimeException $e) {
            }
        }
    }

    protected function publicPath()
    {
        if (function_exists('public_path')) {
            return public_path();
        }

        return $this->appPath() . '/public';

    }

    protected function appPath()
    {
        if (function_exists('app_path')) {
            return app_path();
        }
        return $this->app->__invoke('app')->path();
    }

    protected function url()
    {
        if (function_exists('url')) {
            return url('/');
        }

        return '/';
    }

}
