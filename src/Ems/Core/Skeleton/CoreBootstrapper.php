<?php

namespace Ems\Core\Skeleton;

use Ems\Contracts\Core\Checker as CheckerContract;
use Ems\Contracts\Core\ConnectionPool as ConnectionPoolContract;
use Ems\Contracts\Core\EntityManager as EntityManagerContract;
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
use Ems\Contracts\Core\TextProvider;
use Ems\Contracts\Core\Url as UrlContract;
use Ems\Core\AnythingProvider;
use Ems\Skeleton\Application;
use Ems\Core\ArrayLocalizer;
use Ems\Core\ArrayProvider;
use Ems\Core\ArrayTextProvider;
use Ems\Core\Checker;
use Ems\Core\ConnectionPool;
use Ems\Core\EntityManager;
use Ems\Core\Extractor;
use Ems\Core\FilesystemConnection;
use Ems\Core\Formatter;
use Ems\Core\InputCaster;
use Ems\Core\InputCorrector;
use Ems\Core\LocalFilesystem;
use Ems\Core\ManualMimeTypeProvider;
use Ems\Core\ObjectArrayConverter;
use Ems\Core\PathFinder;
use Ems\Core\Storages\NestedFileStorage;
use Ems\Core\StringConverter\AsciiStringConverter;
use Ems\Core\StringConverter\IconvStringConverter;
use Ems\Core\StringConverter\MBStringConverter;
use Ems\Core\StringConverterChain;
use Ems\Core\Support\RendererChain;
use Ems\Core\TextFormatter;
use Ems\Core\TextParserQueue;
use Ems\Core\Url;
use Ems\Core\VariablesTextParser;
use Ems\Expression\Matcher;
use Ems\Skeleton\Bootstrapper;
use ReflectionClass;
use ReflectionMethod;
use Ems\Contracts\Core\ObjectArrayConverter as ObjectArrayConverterContract;

class CoreBootstrapper extends Bootstrapper
{
    /**
     * @var array
     **/
    protected $singletons = [
        LocalFilesystem::class              => Filesystem::class,
        ManualMimeTypeProvider::class       => MimeTypeProvider::class,
        RendererChain::class                => Renderer::class,
        PathFinder::class                   => PathFinderContract::class,
        InputCorrector::class               => InputCorrectorContract::class,
        InputCaster::class                  => InputCasterContract::class,
        StringConverterChain::class         => StringConverter::class,
        ArrayLocalizer::class               => Localizer::class,
        TextFormatter::class                => TextFormatterContract::class,
        TextParserQueue::class              => TextParser::class,
        Extractor::class                    => ExtractorContract::class,
        Formatter::class                    => FormatterContract::class,
        ConnectionPool::class               => ConnectionPoolContract::class,
        Checker::class                      => CheckerContract::class,
        EntityManager::class                => EntityManagerContract::class,
        ArrayTextProvider::class            => TextProvider::class,
        ObjectArrayConverter::class         => ObjectArrayConverterContract::class
    ];

    /**
     * @var array
     **/
    protected $bindings = [
        'Ems\Core\AppPath' => 'Ems\Contracts\Core\AppPath',
    ];

    protected $aliases = [];

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

        $this->assignPathsToApp($this->app);

        $this->container->on(PathFinder::class, function ($paths) {
            $this->assignBaseAppPaths($paths);
        });

        $this->container->on(StringConverterChain::class, function ($chain) {
            $this->addStringConverters($chain);
        });

        $this->container->on(TextFormatter::class, function ($formatter, $app) {
            $formatter->setLocalizer($app('Ems\Contracts\Core\Localizer'));
        });

        $this->container->bind('ems::locale-config', function ($ioc) {

            $storage = new NestedFileStorage();
            $storage->setUrl($this->app->path('ems-resources')->append('lang'));
            $storage->setNestingLevel(1);

            $provider = new ArrayProvider();
            $provider->add($storage);

            return $provider;

        }, true);

        $this->container->bind(Matcher::class, function ($ioc) {
            return new Matcher($ioc(CheckerContract::class), $ioc(ExtractorContract::class));
        }, true);

        $this->container->on(Formatter::class, function (Formatter $formatter, $app) {
            $formatter->setFormats($app('ems::locale-config'));
        });

        $this->container->on(TextParserQueue::class, function ($queue, $app) {
            $queue->add($app(VariablesTextParser::class));
        });

        $this->container->on(AnythingProvider::class, function ($provider, $app) {
            $provider->createObjectsWith($app);
        });

        $this->container->on(SupportsCustomFactory::class, function ($object) {
            $object->createObjectsBy($this->app);
        });

        $this->container->on(HasInjectMethods::class, function (HasInjectMethods $object) {
            $this->autoInjectDependencies($object);
        });

        $this->container->on(ConnectionPoolContract::class, function (ConnectionPoolContract $pool) {
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
     * @param \Ems\Skeleton\Application $app
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
                $chain->add($this->app->get($class));
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
    protected function autoInjectDependencies(HasInjectMethods $object)
    {

        $reflection = new ReflectionClass($object);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {

            // Method has to start with 'inject'
            if (strpos($method->getName(), 'inject') !== 0) {
                continue;
            }

            foreach ($method->getParameters() as $parameter) {

                // No class|interface typehint, skip the method
                if (!$class = $parameter->getClass()) {
                    continue 2;
                }

                // class|interface typehint not bound, skip the method
                if (!$this->app->has($class->getName())) {
                    continue 2;
                }
            }

            $this->app->call([$object, $method->getName()]);

        }

    }
}
