<?php


namespace Ems\Core\Skeleton;

use Ems\Contracts\Core\PathFinder;

class CoreBootstrapper extends Bootstrapper
{

    protected $singletons = [
        'Ems\Core\LocalFilesystem'          => 'Ems\Contracts\Core\Filesystem',
        'Ems\Core\ManualMimeTypeProvider'   => 'Ems\Contracts\Core\MimeTypeProvider',
        'Ems\Core\Support\RendererChain'    => 'Ems\Contracts\Core\Renderer',
        'Ems\Core\PathFinder'               => 'Ems\Contracts\Core\PathFinder'
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
    }

    protected function assignBaseAppPaths(PathFinder $paths)
    {
        $paths->map('app', $this->appPath(), $this->url());
        $paths->map('assets', $this->publicPath(), $this->url());
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
