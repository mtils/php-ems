<?php
/**
 *  * Created by mtils on 10.08.19 at 19:07.
 **/

namespace Ems\Routing\Skeleton;


use Ems\Console\AnsiRenderer;
use Ems\Contracts\Core\Filesystem;
use Ems\Contracts\Routing\Dispatcher;
use Ems\Contracts\Routing\Input;
use Ems\Contracts\Routing\InputHandler as InputHandlerContract;
use Ems\Contracts\Routing\MiddlewareCollection as MiddlewareCollectionContract;
use Ems\Contracts\Routing\ResponseFactory as ResponseFactoryContract;
use Ems\Contracts\Routing\RouteRegistry as RouteRegistryContract;
use Ems\Routing\RouteRegistry;
use Ems\Contracts\Routing\RouteCollector;
use Ems\Contracts\Routing\Router as RouterContract;
use Ems\Contracts\Routing\UrlGenerator as UrlGeneratorContract;
use Ems\Core\Exceptions\UnsupportedParameterException;
use Ems\Core\Serializer;
use Ems\Core\Serializer\JsonSerializer;
use Ems\Core\Storages\SingleFileStorage;
use Ems\Core\Url;
use Ems\Routing\CompilableRouter;
use Ems\Routing\ConsoleDispatcher;
use Ems\Routing\FastRoute\FastRouteDispatcher;
use Ems\Routing\HttpInput;
use Ems\Routing\InputHandler;
use Ems\Routing\MiddlewareCollection;
use Ems\Routing\ResponseFactory;
use Ems\Routing\RoutedInputHandler;
use Ems\Routing\RouteMiddleware;
use Ems\Routing\Router;
use Ems\Routing\SessionHandler\FileSessionHandler;
use Ems\Routing\SessionMiddleware;
use Ems\Routing\UrlGenerator;
use Ems\Skeleton\Bootstrapper;
use Ems\Skeleton\Routing\RoutesConsoleView;
use Ems\Skeleton\Routing\RoutesController;
use Psr\Http\Message\RequestInterface;

use function method_exists;
use function php_sapi_name;

class RoutingBootstrapper extends Bootstrapper
{
    protected $singletons = [
        InputHandler::class         => InputHandlerContract::class,
        ResponseFactory::class      => ResponseFactoryContract::class
    ];

    protected $bindings = [
        FastRouteDispatcher::class => Dispatcher::class,
        MiddlewareCollection::class => MiddlewareCollectionContract::class,
        UrlGenerator::class         => UrlGeneratorContract::class
    ];

    protected $defaultSessionClients = [Input::CLIENT_WEB, Input::CLIENT_CMS, Input::CLIENT_AJAX, Input::CLIENT_MOBILE];

    protected $defaultConfig = [
        'compile'       => false,
        'cache_driver'  => 'file',
        'cache_file'    => 'local/cache/routes.json'
    ];

    public function bind()
    {
        parent::bind();

        $this->container->share(RouteRegistryContract::class, function () {
            return $this->createRegistry();
        });

        $this->container->share(RouterContract::class, function () {
            /** @var Router $router */
            $router = $this->container->create(Router::class);
            $router->createObjectsBy($this->container);
            /** @var RouteRegistry $registry */
            $registry = $this->container->get(RouteRegistryContract::class);
            $router->fillDispatchersBy([$registry, 'fillDispatcher']);
            return $router;
        });

        $this->container->share(Router::class, function () {
            return $this->container->get(RouterContract::class);
        });

        $this->container->bind(CompilableRouter::class, function () {
            return $this->createCompilableRouter();
        });

        $this->container->onAfter(ConsoleDispatcher::class, function (ConsoleDispatcher $dispatcher) {
            $dispatcher->setFallbackCommand('commands');
        });

        $this->container->onAfter(InputHandler::class, function (InputHandler $handler) {
            $this->addDefaultMiddleware($handler->middleware());
        });

        $this->container->on(AnsiRenderer::class, function (AnsiRenderer $renderer) {
            /** @noinspection PhpParamsInspection */
            $renderer->extend('routes.index', $this->container->create(RoutesConsoleView::class));
        });

        $this->container->on(RouteCompileController::class, function (RouteCompileController $controller) {
            $controller->setStorage($this->createCacheStorage($this->getRoutingConfig()));
        });

        $this->addDefaultRoutes();

    }

    /**
     * Create normal router if compilation is not wanted or cache file does not
     * exist.
     *
     * @return Router
     * @noinspection PhpIncompatibleReturnTypeInspection
     */
    protected function createRegistry() : RouteRegistry
    {
        $config = $this->getRoutingConfig();

        if (!$config['compile']) {
            return $this->container->create(RouteRegistry::class);
        }

        /** @var Filesystem $fileSystem */
        $fileSystem = $this->container->get(Filesystem::class);

        if (!$fileSystem->exists($config['cache_file'])) {
            return $this->container->create(RouteRegistry::class);
        }

        $storage = $this->createCacheStorage($this->getRoutingConfig());
        $compiledData = $storage->toArray();

        if (!isset($compiledData[RouteRegistry::KEY_VALID])) {
            return $this->container->create(RouteRegistry::class);
        }

        /** @var RouteRegistry $registry */
        $registry = $this->container->create(RouteRegistry::class);
        return $registry->setCompiledData($compiledData);
    }

    /** @noinspection PhpIncompatibleReturnTypeInspection */
    protected function createCompilableRouter(RouterContract $base=null) : CompilableRouter
    {
        return $this->container->create(CompilableRouter::class, [
            'router'    => $base ?: $this->container->get(RouterContract::class)
        ]);
    }

    protected function addDefaultMiddleware(MiddlewareCollectionContract $collection)
    {

        $this->addClientTypeMiddleware($collection);
        $this->addRouteScopeMiddleware($collection);
        $this->addSessionMiddleware($collection);
        $this->addRouterToMiddleware($collection);
        $this->addRouteMiddleware($collection);
        $this->addRouteHandlerMiddleware($collection);
    }

    protected function addClientTypeMiddleware(MiddlewareCollectionContract $collection)
    {
        $collection->add('client-type', function (Input $input, callable $next) {

            if ($input->getClientType() || !$input instanceof HttpInput) {
                return $next($input);
            }

            if (php_sapi_name() == 'cli') {
                return $next($input->withClientType(Input::CLIENT_CONSOLE));
            }
            if (!$url = $input->getUrl()) {
                return $next($input->withClientType(Input::CLIENT_WEB));
            }
            if ($url->path->first() == 'api') {
                /** @var RequestInterface|Input $input */
                $input = $input->withClientType(Input::CLIENT_API)
                               ->withUrl($url->shift());
                return $next($input);
            }
            return $next($input->withClientType(Input::CLIENT_WEB));
        });
    }

    protected function addRouteScopeMiddleware(MiddlewareCollectionContract $collection)
    {
        $collection->add('route-scope', function (Input $input, callable $next) {
            if (method_exists($input, 'setRouteScope')) {
                $input->setRouteScope('default');
                return $next($input);
            }
            if (method_exists($input, 'withRouteScope')) {
                $input = $input->withRouteScope('default');
            }
            return $next($input);
        });
    }

    protected function addSessionMiddleware(MiddlewareCollectionContract $collection)
    {
        $config = $this->app->config('session');
        $clientTypes = $config['clients'] ?? $this->defaultSessionClients;
        $collection->add('session', SessionMiddleware::class)->clientType(...$clientTypes);

        if (!$config) {
            return;
        }

        $this->app->on(SessionMiddleware::class, function (SessionMiddleware $middleware) use ($config) {
            $this->configureSessionMiddleware($middleware, $config);
        });
    }

    protected function addRouterToMiddleware(MiddlewareCollectionContract $collection)
    {
        $collection->add('router', RouterContract::class);
    }

    protected function addRouteMiddleware(MiddlewareCollectionContract $collection)
    {
        $collection->add('route-middleware', RouteMiddleware::class);
    }

    protected function addRouteHandlerMiddleware(MiddlewareCollectionContract $collection)
    {
        $collection->add('handle', RoutedInputHandler::class);
    }

    protected function configureSessionMiddleware(SessionMiddleware $middleware, array $config)
    {
        $middleware->extend('files', function () use ($config) {
            $path = $config['path'] ?? $this->appPath() . '/local/storage/sessions';
            /** @var FileSessionHandler $handler */
            $handler = $this->container->create(FileSessionHandler::class);
            $handler->setPath($path);
            return $handler;
        });


        if (isset($config['cookie'])) {
            $middleware->setCookieConfig($config['cookie']);
        }
        if (isset($config['driver']) && $config['driver']) {
            $middleware->setDriver($config['driver']);
        }
        if (isset($config['driver']) && $config['driver']) {
            $middleware->setDriver($config['driver']);
        }
        if (isset($config['serverside_lifetime']) && $config['serverside_lifetime']) {
            $middleware->setLifeTime((int)$config['serverside_lifetime']);
        }
    }

    protected function addDefaultRoutes()
    {
        $this->addRoutesBy(function (RouteCollector $routes) {

            $routes->command(
                'commands',
                ConsoleCommandsController::class.'->index',
                'List all of your console commands.'
            )->argument('?pattern', 'List only commands matching this pattern');

            $routes->command(
                'help',
                ConsoleCommandsController::class.'->show',
                'Show help for one console command.'
            )->argument('command_name', 'The name of the command you need help for.');

            $routes->command(
                'routes',
                RoutesController::class.'->index',
                'List all your routes (and commands)'
            )->argument('?columns', 'What columns to show? v=Verb(Method), p=Pattern, n=Name, c=Clients, s=Scopes, m=Middleware')
             ->option('pattern=*', 'Routes matches this pattern', 'p')
             ->option('client=*', 'Routes of this client types', 'c')
             ->option('name=*', 'Routes with this name', 'n')
             ->option('scope=*', 'Routes of this scope', 's');

            $routes->command(
                'routes:compile',
                [RouteCompileController::class, 'compile'],
                'Optimize routes (compile them into a cache file)'
            );

            $routes->command(
                'routes:compile-status',
                [RouteCompileController::class, 'status'],
                'Check if the rules were compiled'
            );
            $routes->command(
                'routes:clear-compiled',
                [RouteCompileController::class, 'clear'],
                'Clear the compile route cache.'
            );
        });
    }

    /**
     * @return array
     */
    protected function getRoutingConfig() : array
    {
        $config = $this->getConfig('routing');
        if (isset($config['cache_file']) && $config['cache_file'][0] != '/') {
            $config['cache_file'] = (string)$this->app->path($config['cache_file']);
        }
        return $config;
    }

    protected function createCacheStorage(array $config)
    {
        if (isset($config['cache_driver']) && $config['cache_driver'] != 'file') {
            throw new UnsupportedParameterException('I only support cache files for route cache currently not ' . $config['cache_driver']);
        }

        /** @var Filesystem $fileSystem */
        $fileSystem = $this->container->get(Filesystem::class);
        $serializer = $this->createSerializer($fileSystem->extension($config['cache_file']));

        /** @var SingleFileStorage $storage */
        $storage = $this->container->create(SingleFileStorage::class, [
            'filesystem'    => $fileSystem,
            'serializer'    => $serializer
        ]);
        $storage->setUrl(new Url($config['cache_file']));
        return $storage;
    }

    protected function createSerializer(string $extension)
    {
        if ($extension == 'json') {
            return (new JsonSerializer())->asArrayByDefault(true);
        }
        if ($extension == 'phpdata') {
            return new Serializer();
        }

        throw new UnsupportedParameterException("Unknown serialize extension '$extension'");
    }
}