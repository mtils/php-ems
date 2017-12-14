<?php

namespace Ems\Core\Skeleton;

use Ems\Contracts\Core\ConnectionPool as ConnectionPoolContract;
use Ems\Contracts\Core\Extractor as ExtractorContract;
use Ems\Contracts\Core\Filesystem;
use Ems\Contracts\Core\Formatter as FormatterContract;
use Ems\Contracts\Core\HasInjectMethods;
use Ems\Contracts\Core\InputCaster as InputCasterContract;
use Ems\Contracts\Core\InputCorrector as InputCorrectorContract;
use Ems\Contracts\Core\Localizer;
use Ems\Contracts\Core\MimeTypeProvider;
use Ems\Contracts\Core\PathFinder as PathFinderContract;
use Ems\Contracts\Core\Renderer;
use Ems\Contracts\Core\StringConverter;
use Ems\Contracts\Core\SupportsCustomFactory;
use Ems\Contracts\Core\TextFormatter as TextFormatterContract;
use Ems\Contracts\Core\TextParser;
use Ems\Contracts\Core\Url as UrlContract;
use Ems\Core\Application;
use Ems\Core\ArrayLocalizer;
use Ems\Core\ArrayProvider;
use Ems\Core\Extractor;
use Ems\Core\Formatter;
use Ems\Core\InputCaster;
use Ems\Core\InputCorrector;
use Ems\Core\LocalFilesystem;
use Ems\Core\ManualMimeTypeProvider;
use Ems\Core\PathFinder;
use Ems\Core\Storages\NestedFileStorage;
use Ems\Core\TextFormatter;
use Ems\Core\StringConverterChain;
use Ems\Core\StringConverter\MBStringConverter;
use Ems\Core\StringConverter\IconvStringConverter;
use Ems\Core\StringConverter\AsciiStringConverter;
use Ems\Core\Support\RendererChain;
use Ems\Core\TextParserQueue;
use Ems\Core\Url;
use Ems\Core\VariablesTextParser;
use Ems\Core\AnythingProvider;
use Ems\Core\ConnectionPool;
use Ems\Core\FilesystemConnection;
use ReflectionClass;
use ReflectionMethod;

class CoreBootstrapper extends Bootstrapper
{
    /**
     * @var array
     **/
    protected $singletons = [
        LocalFilesystem::class            => Filesystem::class,
        ManualMimeTypeProvider::class     => MimeTypeProvider::class,
        RendererChain::class              => Renderer::class,
        PathFinder::class                 => PathFinderContract::class,
        InputCorrector::class             => InputCorrectorContract::class,
        InputCaster::class                => InputCasterContract::class,
        StringConverterChain::class       => StringConverter::class,
        ArrayLocalizer::class             => Localizer::class,
        TextFormatter::class              => TextFormatterContract::class,
        TextParserQueue::class            => TextParser::class,
        Extractor::class                  => ExtractorContract::class,
        Formatter::class                  => FormatterContract::class,
        ConnectionPool::class             => ConnectionPoolContract::class
    ];

    /**
     * @var array
     **/
    protected $bindings = [
        'Ems\Core\AppPath' => 'Ems\Contracts\Core\AppPath',
    ];

    /**
     * @var Url
     */
    protected $emsResourcePath;

    /**
     * Perform resolving hooks
     *
     * @return void
     **/
    public function bind()
    {
        parent::bind();

        // In environments that are not a skeleton app we use an Application
        // instance to hold the paths.
        if (!$this->app->bound(Application::class)) {
            $this->app->instance(Application::class, $this->createPlaceholderApp());
        }

        $this->assignPathsToApp($this->app->make(Application::class));

        $this->app->resolving(PathFinder::class, function ($paths) {
            $this->assignBaseAppPaths($paths);
        });

        $this->app->resolving(StringConverterChain::class, function ($chain) {
            $this->addStringConverters($chain);
        });

        $this->app->resolving(TextFormatter::class, function ($formatter, $app) {
            $formatter->setLocalizer($app('Ems\Contracts\Core\Localizer'));
        });

        $this->app->bind('ems::locale-config', function ($ioc) {

            /** @var Application $app */
            $app = $ioc(Application::class);
            $storage = new NestedFileStorage();
            $storage->setUrl($app->path('ems-resources')->append('lang'));
            $storage->setNestingLevel(1);

            $provider = new ArrayProvider();
            $provider->add($storage);

            return $provider;

        }, true);

        $this->app->resolving(Formatter::class, function (Formatter $formatter, $app) {
            $formatter->setFormats($app('ems::locale-config'));
        });

        $this->app->resolving(TextParserQueue::class, function ($queue, $app) {
            $queue->add($app(VariablesTextParser::class));
        });

        $this->app->resolving(AnythingProvider::class, function ($provider, $app) {
            $provider->createObjectsWith($app);
        });

        $this->app->resolving(SupportsCustomFactory::class, function ($object) {
            $object->createObjectsBy($this->app);
        });

        $this->app->resolving(HasInjectMethods::class, function (HasInjectMethods $object) {
            $this->autoInjectDependendies($object);
        });

        $this->app->resolving(ConnectionPoolContract::class, function (ConnectionPoolContract $pool) {
            /** @return void */
            $pool->extend('php', function (UrlContract $url) {
                if ($url->scheme == 'php' || $url->scheme == 'file') {
                    return new FilesystemConnection($url);
                }
                return null;
            });
        });
    }

    /**
     * Assign the base application paths
     *
     * @param PathFinder $paths
     **/
    protected function assignBaseAppPaths(PathFinder $paths)
    {
        $emsResources = (string)$this->emsResourcesPath();

        $paths->map('app', $this->appPath(), $this->url());
        $paths->map('assets', $this->publicPath(), $this->url());
        $paths->map('ems::resources', $emsResources, $this->url());

    }

    /**
     * @param Application $app
     */
    protected function assignPathsToApp(Application $app)
    {
        $appPath = new Url($this->appPath());

        $appPaths = [
            'app'           => $appPath,
            'config'        => $appPath->append('config'),
            'public'        => $appPath->append('public'),
            'ems-resources' => $this->emsResourcesPath(),
        ];

        $app->setPaths($appPaths);
    }

    /**
     * @return Application
     */
    protected function createPlaceholderApp()
    {
        return new Application($this->appPath(), $this->app, false);
    }

    /**
     * Dynamically assign all StringConverters (based on installment)
     *
     * @param StringConverterChain $chain
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

        return $this->app->make('app')->path();
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

    /**
     * @return Url
     */
    protected function emsResourcesPath()
    {
        if (!$this->emsResourcePath) {
            $path = realpath(__DIR__.'/../../../../resources');
            $this->emsResourcePath = new Url($path);
        }
        return $this->emsResourcePath;
    }

    /**
     * Auto Inject all dependencies
     *
     * @param HasInjectMethods $object
     **/
    protected function autoInjectDependendies(HasInjectMethods $object)
    {

        $reflection = new ReflectionClass($object);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {

            // Method has to start with 'inject'
            if (strpos($method->getName(), 'inject') !== 0) {
                continue;
            }

            // All parameters has to be some sort of class and bound
            $parameters = $method->getParameters();

            foreach ($method->getParameters() as $parameter) {

                // No class|interface typehint, skip the method
                if (!$class = $parameter->getClass()) {
                    continue 2;
                }

                // class|interface typehint not bound, skip the method
                if (!$this->app->bound($class->getName())) {
                    continue 2;
                }
            }

            $this->app->call([$object, $method->getName()]);

        }

    }
}
