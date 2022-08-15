<?php
/**
 *  * Created by mtils on 17.07.2022 at 09:35.
 **/

namespace integration\Routing;

use Ems\Auth\Auth;
use Ems\Auth\Routing\IsAuthenticatedMiddleware;
use Ems\Auth\Routing\SessionAuthMiddleware;
use Ems\Auth\User;
use Ems\Contracts\Auth\Auth as AuthInterface;
use Ems\Contracts\Auth\LoggedOutException;
use Ems\Contracts\Core\IOCContainer;
use Ems\Contracts\Core\Url as UrlContract;
use Ems\Contracts\Routing\Input;
use Ems\Contracts\Routing\RouteCollector;
use Ems\Contracts\Routing\RouteRegistry;
use Ems\Core\FilterableArray;
use Ems\Core\Lambda;
use Ems\Http\HttpResponse;
use Ems\IntegrationTest;
use Ems\Routing\GenericInput;
use Ems\Routing\HttpInput;
use Ems\Routing\InputHandler;
use Ems\Routing\SessionHandler\ArraySessionHandler;
use Ems\Routing\SessionMiddleware;
use Ems\RoutingTrait;
use Ems\Skeleton\Application;
use Ems\Skeleton\ErrorHandler;
use Ems\Skeleton\Testing\HttpCalls;

use function is_array;

class AuthIntegrationTest extends IntegrationTest
{
    use RoutingTrait;
    use HttpCalls;

    protected const SESSION_ID = 'abcdefgh';

    public static $sessionData = [];

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
    public function check_complete_login_process()
    {

        $response = $this->get('/bar');
        $this->assertInstanceOf(HttpResponse::class, $response);
        $this->assertEquals(401, $response->status);

        // Forgot complex password
        $response = $this->post('/session/create', [
            'email'     => 'kathleen@somewhere.com',
            'password'  => '123'
        ]);

        $this->assertEquals('Wrong password', $response->payload);
        $this->assertEquals(400, $response->status);

        // Got back from password manager
        $response = $this->post('/session/create', [
            'email'     => 'kathleen@somewhere.com',
            'password'  => AuthIntegrationTest_Controller::PASSWORD
        ]);

        $this->assertEquals('User logged in', $response->payload);
        $this->assertEquals(201, $response->status);

        $response = $this->get('/bar');

        $this->assertInstanceOf(HttpResponse::class, $response);
        $this->assertEquals('Hello I am kathleen@somewhere.com', (string)$response->body);
        $this->assertEquals(200, $response->status);

        // Log the user out
        $response = $this->dispatch($this->request('/session', '', 'DELETE'));

        $this->assertEquals(204, $response->status);

        // And it fails again
        $response = $this->get('/bar');
        $this->assertInstanceOf(HttpResponse::class, $response);
        $this->assertEquals(401, $response->status);

    }

    protected function clearSession()
    {
        self::$sessionData = [];
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
                $routes->post('/session/create', [AuthIntegrationTest_Controller::class,'login']);
                $routes->delete('/session', [AuthIntegrationTest_Controller::class,'logout']);
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
                return new ArraySessionHandler(self::$sessionData);
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
    protected function request($url, $payload='', string $method='') : Input
    {
        $input = (new HttpInput($payload))->withClientType(Input::CLIENT_WEB);
        if ($method) {
            $input = $input->withMethod($method);
        }
        if ($method != 'GET' && is_array($payload)) {
            $input = $input->withParsedBody($payload);
        }
        $input = $input->withCookieParams([self::$sessionConfig['cookie']['name'] => self::SESSION_ID]);
        return $input->withUrl($this->toUrl($url));
    }
}

class AuthIntegrationTest_Controller
{
    public const PASSWORD = 'password123';

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

    public function login(AuthInterface $auth, SessionAuthMiddleware $middleware, HttpInput $input) : HttpResponse
    {
        if ($input->get('password') != self::PASSWORD) {
            return new HttpResponse('Wrong password',[], 400);
        }
        /** @var User $user */
        if (!$user = $auth->userByCredentials(['email' => $input->get('email')])) {
            return new HttpResponse('User dos not exist',[], 400);
        }
        $middleware->persistInSession(['id' => $user->id], $input->session);
        return new HttpResponse('User logged in',[], 201);
    }

    public function logout(SessionAuthMiddleware $middleware, HttpInput $input)
    {
        $middleware->removeFromSession($input->session);
        return new HttpResponse('', [], 204);
    }
}