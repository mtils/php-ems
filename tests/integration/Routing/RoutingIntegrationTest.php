<?php
/**
 *  * Created by mtils on 17.01.2022 at 19:03.
 **/

namespace Ems\Routing;

use Ems\Contracts\Http\Cookie;
use Ems\Contracts\Routing\Input;
use Ems\Contracts\Routing\InputHandler as InputHandlerContract;
use Ems\Contracts\Routing\RouteCollector;
use Ems\Contracts\Routing\Router as RouterContract;
use Ems\Core\Response;
use Ems\Core\Url;
use Ems\Http\HttpResponse;
use Ems\HttpMockTest;
use Ems\Routing\SessionHandler\ArraySessionHandler;
use Ems\Skeleton\Application;

use function print_r;
use function spl_object_hash;


class RoutingIntegrationTest extends HttpMockTest
{
    /**
     * @var string[]
     */
    protected static $configuredRouters = [];

    /**
     * @var array
     */
    protected static $session = [];

    static $sessionConfig = [
        'driver'            => 'static-array',
        'lifetime_minutes'  => 15,
        'clients'           => ['web', 'cms'],
        'cookie' => [
            'name'      => 'TEST_SESSION',
            'path'      => '/session',
            'domain'    => 'localhost',
            'secure'    => true,
            'httponly'  => true,
            'samesite'  => 'strict'
        ]
    ];

    /**
     * @test
     */
    public function call_simple_route()
    {
        $output = (new RoutingIntegrationTest_Controller())->foo();
        $response = $this->get('/foo');
        $this->assertInstanceOf(HttpResponse::class, $response);
        $this->assertEquals('text/html', $response->contentType);
        $this->assertEquals($output, $response->body);
    }

    /**
     * @test
     */
    public function session_is_started_when_accessed()
    {
        $input = $this->request([
            'method'        =>  Input::GET,
            'uri'           =>  new Url('/session-write'),
            'clientType'    =>  Input::CLIENT_WEB
        ]);
        $response = $this->dispatch($input);
        $cookieName = static::$sessionConfig['cookie']['name'];
        $this->assertInstanceOf(HttpResponse::class, $response);
        $cookie = $response->cookies[$cookieName];
        $this->assertInstanceOf(Cookie::class, $cookie);
        $this->assertEquals(static::$sessionConfig['cookie']['path'], $cookie->path);
        $this->assertEquals(static::$sessionConfig['cookie']['domain'], $cookie->domain);
        $this->assertEquals(static::$sessionConfig['cookie']['secure'], $cookie->secure);
        $this->assertEquals(static::$sessionConfig['cookie']['httponly'], $cookie->httpOnly);
        $this->assertEquals(static::$sessionConfig['cookie']['samesite'], $cookie->sameSite);

        self::$session = [];
    }

    /**
     * @test
     */
    public function session_is_restored()
    {
        $input = $this->request([
            'method'        =>  Input::GET,
            'uri'           =>  new Url('/session-write'),
            'clientType'    =>  Input::CLIENT_WEB
        ]);
        $response = $this->dispatch($input);
        $cookieName = static::$sessionConfig['cookie']['name'];
        $this->assertInstanceOf(HttpResponse::class, $response);
        $cookie = $response->cookies[$cookieName];

        $input2 = $this->request([
            'method'        =>  Input::GET,
            'uri'           =>  new Url('/session-read'),
            'clientType'    =>  Input::CLIENT_WEB
        ])->withCookieParams([$cookie->name => $cookie->value]);

        $response2 = $this->dispatch($input2);

        $this->assertEquals('bar', $response2->payload);
        // The cookie is not assigned again
        $this->assertFalse(isset($response2->cookies[$cookie->name]));


    }

    protected function get($url, callable $requestHook=null) : Response
    {
        $input = $this->request([
            'method'        =>  Input::GET,
            'uri'           =>  $url instanceof Url ? $url : new Url($url),
            'clientType'    =>  Input::CLIENT_WEB
        ]);

        if ($requestHook) {
            $requestHook($input);
        }
        return $this->dispatch($input);
    }

    protected function request(...$attributes) :  HttpInput
    {
        return new HttpInput(...$attributes);
    }

    /**
     * @param Input $input
     * @return Response
     */
    protected function dispatch(Input $input) : Response
    {
        /** @var InputHandlerContract $handler */
        $handler = $this->app(InputHandlerContract::class);
        return $handler($input);
    }

    /**
     * @param string $path
     * @return Url
     */
    protected function url(string $path) : Url
    {
        return (new Url())
            ->scheme('http')
            ->host(static::$host)
            ->port(static::$port)
            ->path($path);
    }

    protected function boot(Application $app)
    {
        $app->onAfter(RouterContract::class, function (RouterContract $router) {
            $routerId = spl_object_hash($router);
            if (isset(static::$configuredRouters[$routerId])) {
                return;
            }
            $router->register(function (RouteCollector $routes) {
                $routes->get('/foo', RoutingIntegrationTest_Controller::class.'->foo');
                $routes->get('/session-write', RoutingIntegrationTest_Controller::class.'->accessSession');
                $routes->get('/session-read', RoutingIntegrationTest_Controller::class.'->read');
            });
            static::$configuredRouters[$routerId] = true;
        });

    }

    protected function configureApplication(Application $app)
    {
        parent::configureApplication($app);
        $config = $app->getConfig();
        $config['session'] = static::$sessionConfig;
        $app->setConfig($config);
        $app->onBefore(SessionMiddleware::class, function (SessionMiddleware $middleware) {
            $middleware->extend('static-array', function () {
                return new ArraySessionHandler(self::$session);
            });
        });
    }


}

class RoutingIntegrationTest_Controller
{
    public function foo()
    {
        return 'Hello I am the Fritz';
    }

    public function accessSession(HttpInput $input)
    {
        $input->session['foo'] = 'bar';
        return 'Hello';
    }

    public function read(HttpInput $input)
    {
        return $input->session['foo'];
    }
}