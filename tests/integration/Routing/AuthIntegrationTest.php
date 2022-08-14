<?php
/**
 *  * Created by mtils on 17.07.2022 at 09:35.
 **/

namespace integration\Routing;

use Ems\Auth\Routing\IsAuthenticatedMiddleware;
use Ems\Auth\Routing\SessionAuthMiddleware;
use Ems\Auth\User;
use Ems\Contracts\Auth\Auth as AuthInterface;
use Ems\Auth\Auth;
use Ems\Contracts\Auth\LoggedOutException;
use Ems\Contracts\Core\IOCContainer;
use Ems\Contracts\Core\Url as UrlContract;
use Ems\Contracts\Routing\Input;
use Ems\Core\Url;
use Ems\Routing\InputHandler;
use Ems\Contracts\Routing\MiddlewareCollection;
use Ems\Contracts\Routing\RouteCollector;
use Ems\Contracts\Routing\RouteRegistry;
use Ems\Core\FilterableArray;
use Ems\Core\Lambda;
use Ems\Http\HttpResponse;
use Ems\HttpMockTest;
use Ems\IntegrationTest;
use Ems\Routing\GenericInput;
use Ems\Routing\HttpInput;
use Ems\Routing\RoutingIntegrationTest_Controller;
use Ems\Routing\SessionHandler\ArraySessionHandler;
use Ems\Routing\SessionMiddleware;
use Ems\RoutingTrait;
use Ems\Skeleton\Application;
use Ems\Skeleton\ErrorHandler;
use Ems\Skeleton\Testing\HttpCalls;

use function serialize;
use function var_dump;

use const LDAP_CONTROL_ASSERT;

class AuthIntegrationTest extends IntegrationTest
{
    use RoutingTrait;
    use HttpCalls;

    protected const SESSION_ID = 'abcdefgh';

    protected $sessionData = [];

    /**
     * @var string[]
     */
    protected static $configuredRouters = [];

    protected static $sessionConfig = [
        'driver'                => 'static-array',
        'serverside_lifetime'  => 15,
        'clients'               => ['web', 'cms'],
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
        $output = (new AuthIntegrationTest_Controller())->foo();
        $response = $this->get('/foo');
        $this->assertInstanceOf(HttpResponse::class, $response);
        $this->assertEquals('text/html', $response->contentType);
        $this->assertEquals($output, $response->body);

    }

    /**
     * @test
     */
    public function check_user_is_assigned()
    {
        $response = $this->get('/user-check');
        $this->assertInstanceOf(HttpResponse::class, $response);
        $this->assertEquals('text/html', $response->contentType);
        $this->assertEquals('It is nobody@somewhere.com', (string)$response->body);

    }

    /**
     * @test
     */
    public function check_auth_protected_route_fails_without_logged_in_user()
    {
        $response = $this->get('/bar');
        $this->assertInstanceOf(HttpResponse::class, $response);
        $this->assertEquals(401, $response->status);
    }

    /**
     * @test
     */
    public function check_auth_protected_route_succeeds_when_user_is_logged_in()
    {

        /** @var SessionAuthMiddleware $sessionAuthMiddleWare */
        $sessionAuthMiddleWare = $this->app(SessionAuthMiddleware::class);

        $this->sessionData[self::SESSION_ID] = [
            'data' => serialize([
                $sessionAuthMiddleWare->getSessionKey() => ['id' => 5]
            ])
        ];

        $response = $this->get('/bar');

        $this->assertInstanceOf(HttpResponse::class, $response);
        $this->assertEquals('Hello I am kathleen@somewhere.com', (string)$response->body);
        $this->assertEquals(200, $response->status);
    }

    protected function boot(Application $app)
    {
        $app->bind('auth', IsAuthenticatedMiddleware::class);
        $app->onAfter(InputHandler::class, function (InputHandler $handler) {
            $handler->middleware()->add('session-auth', SessionAuthMiddleware::class)->after('session');
        });
        $app->onAfter(RouteRegistry::class, Lambda::once(function (RouteRegistry $registry) {
            $registry->register(function (RouteCollector $routes)  {
                $routes->get('/foo', AuthIntegrationTest_Controller::class.'->foo');
                $routes->get('/bar', AuthIntegrationTest_Controller::class.'->bar')
                    ->middleware('auth');
                $routes->get('/session-write', AuthIntegrationTest_Controller::class.'->accessSession');
                $routes->get('/session-read', AuthIntegrationTest_Controller::class.'->read');
                $routes->get('/user-check', [AuthIntegrationTest_Controller::class,'checkUser']);
            });
        }));

    }

    protected function configureApplication(Application $app)
    {
        parent::configureApplication($app);
        $config = $app->getConfig();
        $config['session'] = static::$sessionConfig;
        $app->setConfig($config);
        $app->onBefore(SessionMiddleware::class, function (SessionMiddleware $middleware) {
            $middleware->extend('static-array', function () {
                return new ArraySessionHandler($this->sessionData);
            });
        });
        $app->bind(AuthInterface::class, function (IOCContainer $app) {
            $users = $this->users();
            $provider = function (array $credentials) use ($users) {
                $results = $users->filter($credentials)->toArray();
                return isset($results[0]) ? $results[0] : null;
            };
            $auth =  $app->create(Auth::class, ['userProvider' => $provider]);
            $auth->setCredentialsForSpecialUser(AuthInterface::GUEST, ['email' => 'nobody@somewhere.com']);
            $auth->setCredentialsForSpecialUser(AuthInterface::SYSTEM, ['email' => 'system@somewhere.com']);
            return $auth;
        });

        $app->on(InputHandler::class, function (InputHandler $handler) use ($app) {
            $handler->setExceptionHandler($app(ErrorHandler::class));
        });

        $app->on(ErrorHandler::class, function (ErrorHandler $handler) {
            $handler->extend(LoggedOutException::class, function (LoggedOutException $e, Input $input) {
                return new HttpResponse($e->getMessage(), [], 401);
            });
        });


    }

    protected function users() : FilterableArray
    {
        return new FilterableArray([
             new User(['id' => 1, 'email' => 'nobody@somewhere.com']),
             new User(['id' => 2, 'email' => 'system@somewhere.com']),
             new User(['id' => 3, 'email' => 'mary@somewhere.com']),
             new User(['id' => 4, 'email' => 'peter@somewhere.com']),
             new User(['id' => 5, 'email' => 'kathleen@somewhere.com'])
         ]);
    }
    /**
     * Create an http request.
     *
     * @param string|UrlContract    $url
     * @param mixed                 $payload
     * @param string                $method
     * @return GenericInput
     */
    protected function request($url, $payload='', string $method='', $sessionCookie=true) : Input
    {
        $input = (new HttpInput($payload))->withClientType(Input::CLIENT_WEB);
        if ($method) {
            $input = $input->withMethod($method);
        }
        if ($sessionCookie) {
            $input = $input->withCookieParams([self::$sessionConfig['cookie']['name'] => self::SESSION_ID]);
        }
        return $input->withUrl($this->toUrl($url));
    }
}

class AuthIntegrationTest_Controller
{
    public function foo() : string
    {
        return 'Hello I am the Fritz';
    }

    public function bar(Input $input) : string
    {
        return 'Hello I am ' . $input->getUser()->email;
    }

    public function accessSession(HttpInput $input) : string
    {
        $input->session['foo'] = 'bar';
        return 'Hello';
    }

    public function read(HttpInput $input)
    {
        return $input->session['foo'];
    }
    public function checkUser(HttpInput $input) : string
    {
        return 'It is ' . $input->getUser()->email;
    }
}