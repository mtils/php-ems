<?php

namespace Ems\Core\Skeleton;

use Ems\Contracts\Core\PathFinder;
use Ems\Core\TextFormatter;
use Ems\Core\StringConverterChain;
use Ems\Core\StringConverter\MBStringConverter;
use Ems\Core\StringConverter\IconvStringConverter;
use Ems\Core\StringConverter\AsciiStringConverter;
use Ems\Core\TextParserQueue;
use Ems\Core\VariablesTextParser;
use Ems\Core\AnythingProvider;

class CoreBootstrapper extends Bootstrapper
{
    /**
     * @var array
     **/
    protected $singletons = [
        'Ems\Core\LocalFilesystem'        => 'Ems\Contracts\Core\Filesystem',
        'Ems\Core\ManualMimeTypeProvider' => 'Ems\Contracts\Core\MimeTypeProvider',
        'Ems\Core\Support\RendererChain'  => 'Ems\Contracts\Core\Renderer',
        'Ems\Core\PathFinder'             => 'Ems\Contracts\Core\PathFinder',
        'Ems\Core\InputCorrector'         => 'Ems\Contracts\Core\InputCorrector',
        'Ems\Core\InputCaster'            => 'Ems\Contracts\Core\InputCaster',
        'Ems\Core\StringConverterChain'   => 'Ems\Contracts\Core\StringConverter',
        'Ems\Core\ArrayLocalizer'         => 'Ems\Contracts\Core\Localizer',
        'Ems\Core\TextFormatter'          => 'Ems\Contracts\Core\TextFormatter',
        'Ems\Core\TextParserQueue'        => 'Ems\Contracts\Core\TextParser',
        'Ems\Core\Extractor'              => 'Ems\Contracts\Core\Extractor'
    ];

    /**
     * @var array
     **/
    protected $bindings = [
        'Ems\Core\AppPath' => 'Ems\Contracts\Core\AppPath',
    ];

    /**
     * Perform resolving hooks
     **/
    public function bind()
    {
        parent::bind();

        $this->app->resolving(PathFinder::class, function ($paths) {
            $this->assignBaseAppPaths($paths);
        });

        $this->app->resolving(StringConverterChain::class, function ($chain) {
            $this->addStringConverters($chain);
        });

        $this->app->resolving(TextFormatter::class, function ($formatter, $app) {
            $formatter->setLocalizer($app('Ems\Contracts\Core\Localizer'));
        });

        $this->app->resolving(TextParserQueue::class, function ($queue, $app) {
            $queue->add($app(VariablesTextParser::class));
        });

        $this->app->resolving(AnythingProvider::class, function ($provider, $app) {
            $provider->createObjectsWith($app);
        });
    }

    /**
     * Assign the base application paths
     *
     * @param PathFinder $paths
     **/
    protected function assignBaseAppPaths(PathFinder $paths)
    {
        $paths->map('app', $this->appPath(), $this->url());
        $paths->map('assets', $this->publicPath(), $this->url());
        $paths->map('ems::resources', realpath(__DIR__.'/../../../../resources'), $this->url());
    }

    /**
     * Dynamically assign all StringConverters (based on installment)
     *
     * @param StringConverterChain
     */
    protected function addStringConverters(StringConverterChain $chain)
    {
        $classes = [
            IconvStringConverter::class,
            MBStringConverter::class,
            AsciiStringConverter::class,
        ];

        foreach ($classes as $class) {
            try {
                $chain->add($this->app->make($class));
            } catch (\RuntimeException $e) {
            }
        }
    }

    /**
     * Return the applications public path (containing the serving index.php)
     *
     * @return string
     **/
    protected function publicPath()
    {
        if (function_exists('public_path')) {
            return public_path();
        }

        return $this->appPath().'/public';
    }

    /**
     * Return the applications base path (the vcs root directory)
     *
     * @return string
     **/
    protected function appPath()
    {
        if (function_exists('app_path')) {
            return app_path();
        }

        return $this->app->__invoke('app')->path();
    }

    /**
     * Return the application url
     *
     * @return string
     **/
    protected function url()
    {
        if (function_exists('url')) {
            return url('/');
        }

        return '/';
    }
}
