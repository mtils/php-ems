<?php
/**
 *  * Created by mtils on 06.07.19 at 20:04.
 **/

namespace Ems\Routing;


use Ems\Contracts\Cache\Cache;
use Ems\Contracts\Core\Response;
use Ems\Contracts\Routing\Routable;
use Ems\Contracts\Routing\Route;
use Ems\Contracts\Routing\RouteCollector;
use Ems\Contracts\Validation\Validator;
use Ems\Core\Input;
use Ems\Core\IOCContainer;
use Ems\Core\Url;
use Ems\TestCase;
use Ems\Contracts\Routing\Router as RouterContract;
use Ems\Contracts\Routing\Dispatcher as DispatcherContract;
use Ems\TestData;
use Ems\View\View;

class RouterTest extends TestCase
{
    use TestData;

    /**
     * @test
     */
    public function it_implements_router_interface()
    {
        $this->assertInstanceOf(RouterContract::class, $this->make());
    }

    /**
     * @test
     */
    public function it_routes_a_static_route()
    {
        $dispatcher = $this->makeDispatcher();
        $router = $this->make($dispatcher);

        $dispatcher->register(function (RouteCollector $collector) {
            $collector->get('projects', RouterTest_ProjectController::class.'->index');
        });

        $input = $this->makeInput('projects');
        $response = $router->handle($input);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('index()', $response->payload());

    }

    /**
     * @test
     */
    public function it_routes_a_simple_route_with_injection()
    {
        $dispatcher = $this->makeDispatcher();
        $router = $this->make($dispatcher);
        $container = $this->makeContainer();

        $cache = $this->mock(Cache::class);
        $validator = $this->mock(Validator::class);

        $container->instance(Cache::class, $cache);
        $container->instance(Validator::class, $validator);

        $router->createObjectsBy($container);

        $dispatcher->register(function (RouteCollector $collector) {
            $collector->get('projects', RouterTest_ProjectController::class.'->index');
            $collector->get('projects/{project_id}', RouterTest_ProjectController::class.'->show');
        });

        $input = $this->makeInput('projects/244');
        $response = $router->handle($input);
        $this->assertInstanceOf(Response::class, $response);

        $this->assertSame($cache, $response['cache']);
        $this->assertSame($validator, $response['validator']);
        $this->assertEquals('244', $response['id']);
        $this->assertInstanceOf(View::class, $response->payload());

    }

    /**
     * @test
     */
    public function it_routes_a_complex_route_with_injection_and_custom_response()
    {
        $dispatcher = $this->makeDispatcher();
        $router = $this->make($dispatcher);
        $container = $this->makeContainer();

        $cache = $this->mock(Cache::class);
        $validator = $this->mock(Validator::class);

        $container->instance(Cache::class, $cache);
        $container->instance(Validator::class, $validator);

        $router->createObjectsBy($container);

        $dispatcher->register(function (RouteCollector $collector) {
            $collector->get('users/{user_id}/addresses/{address_id}', RouterTest_ProjectController::class.'->showAddress');
        });

        $input = $this->makeInput('users/123/addresses/45');
        $response = $router->handle($input);
        $this->assertInstanceOf(Response::class, $response);

        $view = $response->payload();
        $this->assertSame($cache, $view['cache']);
        $this->assertSame($validator, $view['validator']);
        $this->assertEquals('123', $view['userId']);
        $this->assertEquals('45', $view['addressId']);
        $this->assertInstanceOf(View::class, $response->payload());

    }

    /**
     * @test
     */
    public function it_routes_a_static_command()
    {
        $dispatcher = $this->makeDispatcher();
        $router = $this->make($dispatcher);

        $dispatcher->register(function (RouteCollector $collector) {
            $collector->get('migrate', RouterTest_ProjectController::class.'@migrate')
                      ->clientType(Routable::CLIENT_CONSOLE);
        });

        $input = $this->makeInput('migrate', [], '');
        $response = $router->handle($input);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('Nothing to migrate.', $response->payload());

    }

    /**
     * @test
     */
    public function it_behaves_like_middleware()
    {
        $dispatcher = $this->makeDispatcher();
        $router = $this->make($dispatcher);

        $dispatcher->register(function (RouteCollector $collector) {
            $collector->get('projects', RouterTest_ProjectController::class.'->index');
        });

        $input = $this->makeInput('projects');

        $nextMiddleware = function (Input $input) {
            return $input['nextWasHere'] = 'test';
        };

        $response = $router($input, $nextMiddleware);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('index()', $response->payload());
        $this->assertEquals('test', $input['nextWasHere']);

    }

    /**
     * @test
     */
    public function methodHooks_returns_all_hooks()
    {
        $hooks = [
            Router::PHASE_DISPATCH,
            Router::PHASE_ROUTE,
            Router::PHASE_INSTANTIATE,
            Router::PHASE_CALL,
            Router::PHASE_RESPOND
        ];

        $router = $this->make();

        foreach ($hooks as $hook) {
            $this->assertContains($hook, $router->methodHooks());
        }
    }

    /**
     * @test
     */
    public function routes_returns_routes()
    {

        $dispatcher = $this->makeDispatcher();
        $router = $this->make($dispatcher);

        $dispatcher->register(function (RouteCollector $collector) {
            $collector->get('projects', RouterTest_ProjectController::class.'->index');
            $collector->get('projects/{project_id}', RouterTest_ProjectController::class.'->show');
        });

        $routes = [];
        foreach ($router->routes() as $route) {
            $routes[] = $route;
        }

        $this->assertCount(2, $routes);
        $this->assertContainsOnlyInstancesOf(Route::class, $routes);
    }

    protected function make(DispatcherContract $dispatcher=null)
    {
        return new Router($dispatcher ?: $this->makeDispatcher());
    }

    protected function makeDispatcher()
    {
        return new Dispatcher();
    }

    protected function makeContainer()
    {
        return new IOCContainer();
    }

    /**
     * @param string $path
     * @param array  $parameters (optional)
     * @param string $clientType (optional)
     *
     * @return Input
     */
    protected function makeInput($path, array $parameters=[], $clientType=Routable::CLIENT_WEB)
    {
        $input = new Input();
        foreach ($parameters as $key=>$value) {
            $input[$key] = $value;
        }
        $input->setUrl(new Url($path));
        return $input->setMethod('GET')->setClientType($clientType);
    }
}

class RouterTest_ProjectController
{
    public function index()
    {
        return 'index()';
    }

    public function show(Cache $cache, Validator $validator, $id)
    {
        return (new View('users.show'))->assign([
            'cache'     => $cache,
            'validator' => $validator,
            'id'        => $id
        ]);
    }

    public function showAddress(Cache $cache, $userId, Validator $validator, $addressId)
    {
        $view = (new View('users.show'))->assign([
            'cache'     => $cache,
            'userId'    => $userId,
            'validator' => $validator,
            'addressId' => $addressId
        ]);
        return (new \Ems\Core\Response())->setPayload($view);
    }

    public function migrate()
    {
        return 'Nothing to migrate.';
    }
}