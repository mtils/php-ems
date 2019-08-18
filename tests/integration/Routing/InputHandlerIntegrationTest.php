<?php
/**
 *  * Created by mtils on 21.07.19 at 14:33.
 **/

namespace Ems\Routing;

use ArgumentCountError;
use Ems\Cache\Exception\CacheMissException;
use Ems\Contracts\Core\Filesystem;
use Ems\Contracts\Core\Input;
use Ems\Contracts\Core\InputHandler as HandlerContract;
use Ems\Contracts\Core\Response;
use Ems\Contracts\Core\StringConverter;
use Ems\Contracts\Core\TextFormatter;
use Ems\Contracts\Core\Url as UrlContract;
use Ems\Contracts\Routing\Routable;
use Ems\Contracts\Routing\RouteCollector;
use Ems\Contracts\Routing\Router as RouterContract;
use Ems\Core\Url;
use Ems\RoutingTrait;
use Ems\Testing\LoggingCallable;
use Illuminate\Contracts\Container\Container;
use function array_filter;
use function array_values;
use function class_exists;
use function explode;
use function func_get_args;
use function implode;
use function is_numeric;
use function strpos;


class InputHandlerIntegrationTest extends \Ems\IntegrationTest
{
    use RoutingTrait;

    protected $controllerReplace = [
        'UserController'        => InputHandlerIntegrationTest_UserController::class,
        'UserAddressController' => InputHandlerIntegrationTest_UserAddressController::class
    ];

    /**
     * @test
     */
    public function it_implements_interface()
    {
        $this->assertInstanceOf(HandlerContract::class, $this->makeHandler());
    }

    /**
     * @test
     */
    public function standard_routing_stack_routes_to_custom_controller()
    {
        $handler = $this->makeHandler();

        $router = $this->makeRouter();

        foreach (static::$testRoutes as $routeData) {
            $input = $this->input($routeData['uri'], [], $routeData['method']);
            $response = $handler($input);
            $this->assertInstanceOf(Response::class, $response);
            $string = InputHandlerIntegrationTest_UserController::buildResponse($routeData);
            $this->assertEquals($string, $response->payload());
        }

    }

    /**
     * @test
     */
    public function standard_routing_stack_routes_to_custom_controller_with_method_dependencies()
    {
        $handler = $this->makeHandler();

        // Just make a test with some random dependencies. If they would not
        // be injected a fatal error would occur so we can be safe here
        // The only missing thing was to be sure the dependency controller is
        // really used for routing. And this is ensured by the custom prefix
        // in buildResponse
        $router = $this->makeRouter(true, [
            'UserController' => InputHandlerIntegrationTest_UserController_Dependencies::class,
            'UserAddressController' => InputHandlerIntegrationTest_UserController_Dependencies::class
        ]);

        foreach (static::$testRoutes as $routeData) {
            $input = $this->input($routeData['uri'], [], $routeData['method']);
            $response = $handler($input);
            $this->assertInstanceOf(Response::class, $response);
            $string = InputHandlerIntegrationTest_UserController_Dependencies::buildResponse($routeData);
            $this->assertEquals($string, $response->payload());
        }

    }

    /**
     * @test
     * @expectedException  \Ems\Cache\Exception\CacheMissException
     */
    public function exceptions_are_thrown_without_an_assigned_handler()
    {
        $handler = $this->makeHandler();

        $router = $this->makeRouter(false);

        $router->register(function (RouteCollector $routes) {
            $routes->get('foo', function () {
                throw new CacheMissException();
            });
        });

        $handler($this->input('foo'));

    }

    /**
     * @test
     */
    public function throwables_are_thrown_without_an_assigned_handler()
    {
        // PHP7.0 has no ArgumentCountError
        if (!class_exists(ArgumentCountError::class)) {
            return;
        }
        $handler = $this->makeHandler();

        $router = $this->makeRouter(false);

        $router->register(function (RouteCollector $routes) {
            $routes->get('foo', function () {
                throw new ArgumentCountError();
            });
        });

        try {
            $handler($this->input('foo'));
            $this->fail('The exception was not thrown');
        } catch (ArgumentCountError $e) {
            $this->assertInstanceOf(ArgumentCountError::class, $e);
        }


    }

    /**
     * @test
     */
    public function thrown_exceptions_are_passed_to_the_handler()
    {
        $handler = $this->makeHandler();
        $errorHandler = function ($e) {
            $response = new \Ems\Core\Response();
            $response->setPayload($e);
            return $response;
        };

        $this->assertSame($handler, $handler->setExceptionHandler($errorHandler));
        $this->assertSame($errorHandler, $handler->getExceptionHandler());

        $exception = new CacheMissException();
        $router = $this->makeRouter(false);

        $router->register(function (RouteCollector $routes) use ($exception) {
            $routes->get('foo', function () use ($exception) {
                throw $exception;
            });
        });

        $response = $handler($this->input('foo'));

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame($exception, $response->payload());
    }

    /**
     * @test
     */
    public function thrown_throwables_are_passed_to_the_handler()
    {
        // PHP7.0 has no ArgumentCountError
        if (!class_exists(ArgumentCountError::class)) {
            return;
        }
        $handler = $this->makeHandler();
        $errorHandler = function ($e) {
            $response = new \Ems\Core\Response();
            $response->setPayload($e);
            return $response;
        };

        $this->assertSame($handler, $handler->setExceptionHandler($errorHandler));
        $this->assertSame($errorHandler, $handler->getExceptionHandler());

        $exception = new ArgumentCountError();
        $router = $this->makeRouter(false);

        $router->register(function (RouteCollector $routes) use ($exception) {
            $routes->get('foo', function () use ($exception) {
                throw $exception;
            });
        });

        $response = $handler($this->input('foo'));

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame($exception, $response->payload());
    }

    /**
     * @test
     */
    public function route_middleware_runs_assigned_middleware()
    {
        $handler = $this->makeHandler();
        $router = $this->makeRouter(false);
        $app = $this->app();

        $app->bind('require-auth', function () {
            return function (Input $input, callable $next) {
                $input['i_was_here'] = 'require-auth';
                $next($input);
            };
        });

        $router->register(function (RouteCollector $routes) {
            $routes->get('my-account', function (Input $input) {
                return 'my-account was called and ' . $input['i_was_here'];
            })->middleware('require-auth');
        });

        $response = $handler($this->input('my-account'));

        $this->assertEquals('my-account was called and require-auth', $response->payload());

    }

    /**
     * @test
     */
    public function route_middleware_runs_assigned_middleware_with_parameters()
    {
        $handler = $this->makeHandler();
        $router = $this->makeRouter(false);
        $app = $this->app();

        $app->bind('require-auth', function () {
            return function (Input $input, callable $next, $a, $b='') {
                $input['i_was_here'] = 'require-auth ' . $a . '|' . $b;
                $next($input);
            };
        });

        $router->register(function (RouteCollector $routes) {
            $routes->get('my-account', function (Input $input) {
                return 'my-account was called and ' . $input['i_was_here'];
            })->middleware('require-auth:a,b');
        });

        $response = $handler($this->input('my-account'));

        $this->assertEquals('my-account was called and require-auth a|b', $response->payload());

    }

    /**
     * @test
     */
    public function route_middleware_runs_multiple_assigned_middleware_with_parameters()
    {
        $handler = $this->makeHandler();
        $router = $this->makeRouter(false);
        $app = $this->app();

        $app->bind('require-auth', function () {
            return function (Input $input, callable $next, $a, $b='') {
                $input['i_was_here'] = 'require-auth ' . $a . '|' . $b;
                $next($input);
            };
        });

        $app->bind('require-role', function () {
            return function (Input $input, callable $next, $role, $force='force') {
                $input['i_was_here2'] = 'require-role ' . $role . '|' . $force;
                $next($input);
            };
        });

        $router->register(function (RouteCollector $routes) {
            $routes->get('my-account', function (Input $input) {
                return 'my-account was called and ' . $input['i_was_here'] . '--' . $input['i_was_here2'];
            })->middleware('require-auth:a,b', 'require-role:moderator');
        });

        $response = $handler($this->input('my-account'));

        $this->assertEquals('my-account was called and require-auth a|b--require-role moderator|force', $response->payload());

    }

    /**
     * @test
     */
    public function route_middleware_skips_routed_if_response_returned()
    {
        $handler = $this->makeHandler();
        $router = $this->makeRouter(false);
        $app = $this->app();

        $app->bind('require-token', function () {
            return function (Input $input, callable $next) {
                return new \Ems\Core\Response('I dont care about the next middlewares');
            };
        });

        $router->register(function (RouteCollector $routes) {
            $routes->get('my-account', function (Input $input) {
                return 'my-account was called';
            })->middleware('require-token');

            $routes->get('home', function (Input $input) {
                return 'home was called';
            });
        });

        $response = $handler($this->input('my-account'));

        $this->assertEquals('I dont care about the next middlewares', $response->payload());

        $response = $handler($this->input('home'));

        $this->assertEquals('home was called', $response->payload());

    }
    /**
     * @param Container|null $container
     *
     * @return InputHandler
     */
    protected function makeHandler(Container $container=null)
    {
        return $this->app(HandlerContract::class);
    }

    /**
     * @return Router
     */
    protected function makeRouter($filled=true, $controllerReplace=[])
    {
        /** @var RouterContract $router */
        $router = $this->app(RouterContract::class);
        if ($filled) {
            $this->fillIfNotFilled($router, $controllerReplace ?: $this->controllerReplace);
        }
        return $router;
    }

    protected function input($url, array $parameters=[], $method=Routable::GET, $clientType=Routable::CLIENT_WEB)
    {
        $input = new \Ems\Core\Input();
        $input->setUrl($url instanceof UrlContract ? $url : new Url($url));
        $input->setClientType($clientType);
        $input->setMethod($method);
        foreach ($parameters as $key=>$value) {
            $input[$key] = $value;
        }
        return $input;
    }

}

class InputHandlerIntegrationTest_UserController
{

    public static function buildResponse(array $routeData, $prefix='')
    {
        $methodName = strpos($routeData['handler'], '@') ? explode('@', $routeData['handler'])[1] : $routeData['handler'];

        $response = "$prefix$methodName was called";

        if (!isset($routeData['parameters']) || !$routeData['parameters']) {
            return $response;
        }

        return $response . ' with #' . implode(' #', array_values($routeData['parameters']));
    }

    public static function routeArgs(array $funcArgs)
    {
        return array_filter($funcArgs, function ($arg) {
            return is_numeric($arg);
        });
    }

    public function index()
    {
        return static::buildResponse(['handler' => __FUNCTION__]);
    }

    public function store()
    {
        return static::buildResponse(['handler' => __FUNCTION__]);
    }

    public function create()
    {
        return static::buildResponse(['handler' => __FUNCTION__]);
    }

    public function show($id)
    {
        return static::buildResponse(['handler' => __FUNCTION__, 'parameters' => func_get_args()]);
    }

    public function edit($id)
    {
        return static::buildResponse(['handler' => __FUNCTION__, 'parameters' => func_get_args()]);
    }

    public function update($id)
    {
        return static::buildResponse(['handler' => __FUNCTION__, 'parameters' => func_get_args()]);
    }

    public function destroy($id)
    {
        return static::buildResponse(['handler' => __FUNCTION__, 'parameters' => func_get_args()]);
    }
}

class InputHandlerIntegrationTest_UserAddressController
{
    public function editParent($userId, $parentId, $addressId)
    {
        return InputHandlerIntegrationTest_UserController::buildResponse(['handler' => __FUNCTION__, 'parameters' => func_get_args()]);
    }
}

class InputHandlerIntegrationTest_UserController_Dependencies
{

    public static function buildResponse(array $routeData, $prefix='alternate ')
    {
        return InputHandlerIntegrationTest_UserController::buildResponse($routeData, $prefix);
    }

    public function index(Filesystem $fs)
    {
        return static::buildResponse(['handler' => __FUNCTION__]);
    }

    public function store()
    {
        return static::buildResponse(['handler' => __FUNCTION__]);
    }

    public function create()
    {
        return static::buildResponse(['handler' => __FUNCTION__]);
    }

    public function show(StringConverter $converter, $id)
    {
        $args = InputHandlerIntegrationTest_UserController::routeArgs(func_get_args());
        return static::buildResponse(['handler' => __FUNCTION__, 'parameters' => $args]);
    }

    public function edit($id, StringConverter $converter)
    {
        $args = InputHandlerIntegrationTest_UserController::routeArgs(func_get_args());
        return static::buildResponse(['handler' => __FUNCTION__, 'parameters' => $args]);
    }

    public function update(StringConverter $converter, $id, TextFormatter $formatter)
    {
        $args = InputHandlerIntegrationTest_UserController::routeArgs(func_get_args());
        return static::buildResponse(['handler' => __FUNCTION__, 'parameters' => $args]);
    }

    public function destroy($id)
    {
        $args = InputHandlerIntegrationTest_UserController::routeArgs(func_get_args());
        return static::buildResponse(['handler' => __FUNCTION__, 'parameters' => $args]);
    }

    public function editParent($userId, $parentId, $addressId)
    {
        $args = InputHandlerIntegrationTest_UserController::routeArgs(func_get_args());
        return static::buildResponse(['handler' => __FUNCTION__, 'parameters' => $args]);
    }
}