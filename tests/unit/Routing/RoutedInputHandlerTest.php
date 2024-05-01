<?php
/**
 *  * Created by mtils on 30.06.19 at 11:12.
 **/

namespace Ems\Routing;


use Ems\Contracts\Core\Url as UrlContract;
use Ems\Contracts\Routing\Input;
use Ems\Contracts\Routing\Route;
use Ems\Core\Lambda;
use Ems\Core\Response as CoreResponse;
use Ems\Core\Url;
use Ems\Http\HttpResponse;
use Ems\TestCase;
use Ems\Testing\LoggingCallable;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use function func_get_args;
use function implode;
use function is_callable;
use ReflectionException;

class RoutedInputHandlerTest extends TestCase
{

    #[Test] public function it_implements_interface()
    {
        $this->assertInstanceOf(\Ems\Contracts\Routing\InputHandler::class, $this->make());
    }

    #[Test] public function it_throws_exception_if_input_not_routed()
    {
        $this->expectException(
            \Ems\Core\Exceptions\UnConfiguredException::class
        );
        $handler = $this->make();
        $handler($this->input('home'));
    }

    #[Test] public function it_throws_exception_if_input_handler_not_callable()
    {
        $this->expectException(
            \Ems\Core\Exceptions\UnConfiguredException::class
        );
        $handler = $this->make();
        /** @var Input|Mockery\MockInterface $input */
        $input = $this->mock(Input::class);
        $input->shouldReceive('isRouted')->andReturn(true);
        $input->shouldReceive('getHandler')->andReturn(false);

        $handler($input);
    }

    #[Test] public function it_calls_the_route_handler_and_creates_HttpResponse()
    {
        $handler = $this->make();
        $f = new LoggingCallable(function () {
            return 'bar';
        });
        $input = $this->routedInput('some-url', $f);

        $response = $handler($input);
        $this->assertInstanceOf(HttpResponse::class, $response);
        $this->assertEquals('bar', $response->payload);
    }

    #[Test] public function it_calls_the_route_handler_and_creates_CoreResponse()
    {
        $handler = $this->make();
        $f = new LoggingCallable(function () {
            return 'bar';
        });
        $input = $this->routedInput('some-url', $f);
        $input->setClientType(Input::CLIENT_CONSOLE);

        $response = $handler($input);
        $this->assertNotInstanceOf(HttpResponse::class, $response);
        $this->assertInstanceOf(CoreResponse::class, $response);
        $this->assertEquals('bar', $response->payload);
    }

    #[Test] public function it_calls_the_route_handler_and_passes_response_if_is_already_Response()
    {
        $handler = $this->make();
        $awaited = new HttpResponse('hello');
        $f = new LoggingCallable(function () use ($awaited) {
            return $awaited;
        });
        $input = $this->routedInput('some-url', $f);
        $input->setClientType(Input::CLIENT_CONSOLE);

        $this->assertSame($awaited, $handler($input));
    }

    /**
     * @throws ReflectionException
     */
    #[Test] public function it_assigns_factory_if_lambda_and_none_assigned()
    {
        $factory = function ($class) {
            $instance = new $class;
            $instance->add = ' foo';
            return $instance;
        };

        $handlerString = RoutedInputHandlerTest_TestController::class.'->show';
        $handler = $this->make($factory);

        $f = Lambda::f($handlerString);

        $input = $this->routedInput('some-url', $handlerString, $f);
        $this->assertSame('show was called: foo', $handler($input)->payload);
    }

    protected function make(callable $factory=null)
    {
        return new RoutedInputHandler($factory);
    }

    /**
     * @param $url
     * @param string $method
     * @param string $clientType
     * @param string $scope
     *
     * @return GenericInput
     */
    protected function input($url, string $method=Input::GET, string $clientType=Input::CLIENT_WEB, string $scope='default')
    {
        $routable = new GenericInput();
        if (!$url instanceof UrlContract) {
            $url = new Url($url);
        }
        return $routable->setMethod($method)->setUrl($url)->setClientType($clientType)->setRouteScope($scope);
    }

    /**
     * @param $url
     * @param mixed $handler
     * @return Input
     */
    protected function routedInput($url, $handler, callable $realHandler=null)
    {
        $uri = $url instanceof UrlContract ? (string)$url->path : $url;
        $input = $this->input($url);
        $route = new Route($input->getMethod(), $uri, $handler);
        $handler = is_callable($handler) ? $handler : function () {};
        return $input->makeRouted($route, $realHandler ?: $handler);
    }
}

class RoutedInputHandlerTest_TestController
{
    public $add = '';

    public function index()
    {
        return 'index was called: ' . implode(',', func_get_args());
    }

    public function edit()
    {
        return 'edit was called: ' . implode(',', func_get_args());
    }

    public function store()
    {
        return 'update was called: ' . implode(',', func_get_args());
    }

    public function show()
    {
        return 'show was called:' . $this->add  . implode(',', func_get_args());
    }
}