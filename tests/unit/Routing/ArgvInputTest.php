<?php
/**
 *  * Created by mtils on 18.07.2021 at 10:56.
 **/

namespace Ems\Routing;


use Ems\Contracts\Core\Url;
use Ems\Contracts\Routing\Command;
use Ems\Contracts\Routing\Input;
use Ems\Contracts\Routing\Route;
use Ems\Contracts\Routing\RouteScope;
use Ems\TestCase;

use PHPUnit\Framework\Attributes\Test;

use function get_class;

class ArgvInputTest extends TestCase
{
    #[Test] public function it_implements_interface()
    {
        $this->assertInstanceOf(ArgvInput::class, $this->make());
    }

    #[Test] public function it_assigns_arguments_as_query_parameters_on_get()
    {
        $tenant = '101';
        $input = $this->make(['console', $tenant]);

        $command = (new Command('users:index'))->argument('tenant');
        $route = new Route('GET', 'users', '');
        $route->command($command);
        $input = $input->makeRouted($route, function () {});

        $this->assertEquals($tenant, $input->get('tenant'));

    }

    #[Test] public function it_assigns_arguments_as_query_parameters_on_getOfFail()
    {
        $tenant = '101';
        $input = $this->make(['console', $tenant]);

        $command = (new Command('users:index'))->argument('tenant');
        $route = new Route('GET', 'users', '');
        $route->command($command);
        $input = $input->makeRouted($route, function () {});

        $this->assertEquals($tenant, $input->getOrFail('tenant'));

    }

    #[Test] public function it_assigns_arguments_as_query_parameters_on_offsetExists()
    {
        $tenant = '101';
        $input = $this->make(['console', $tenant]);

        $command = (new Command('users:index'))->argument('tenant');
        $route = new Route('GET', 'users', '');
        $route->command($command);
        $input = $input->makeRouted($route, function () {});

        $this->assertTrue($input->offsetExists('tenant'));
        $this->assertFalse($input->offsetExists('foo'));

    }

    #[Test] public function it_assigns_arguments_as_query_parameters_on_offsetGet()
    {
        $tenant = '101';
        $input = $this->make(['console', $tenant]);

        $command = (new Command('users:index'))->argument('tenant');
        $route = new Route('GET', 'users', '');
        $route->command($command);
        $input = $input->makeRouted($route, function () {});

        $this->assertEquals($tenant, $input['tenant']);

    }

    #[Test] public function it_assigns_arguments_as_query_parameters_on_toArray()
    {
        $tenant = '101';
        $input = $this->make(['console', $tenant]);

        $command = (new Command('users:index'))->argument('tenant');
        $route = new Route('GET', 'users', '');
        $route->command($command);
        $input = $input->makeRouted($route, function () {});

        $inputData = $input->toArray();
        $this->assertEquals($tenant, $inputData['tenant']);
    }

    #[Test] public function it_assigns_options_as_query_parameters_on_get()
    {
        $tenant = '101';
        $force = '';
        $input = $this->make(['console', $tenant, '--force']);

        $command = (new Command('users:index'))->argument('tenant')
        ->option('force');
        $route = new Route('GET', 'users', '');
        $route->command($command);
        $input = $input->makeRouted($route, function () {});

        $this->assertTrue($input->get('force'));
        $this->assertEquals($tenant, $input->get('tenant'));

    }

    #[Test] public function it_is_routable()
    {
        $argv = ['console', 'users:show', '--parameters=3', '--long'];
        $input = $this->make($argv);

        $route = new Route('GET', 'users', '');
        $handler = function () {};
        $parameters = [3];

        $routed = $input->makeRouted($route, $handler, $parameters);
        $this->assertNotSame($routed, $input);
        $this->assertSame(get_class($routed), get_class($input));

        $this->assertSame($route, $routed->getMatchedRoute());
        $this->assertSame($handler, $routed->getHandler());
        $this->assertSame($parameters, $routed->getRouteParameters());

        $this->assertSame($argv, $input->getArgv());
        $this->assertSame($argv, $routed->getArgv());

    }

    #[Test] public function property_access()
    {
        // [X] argv
        // [x] arguments
        // [x] options
        // [X] matchedRoute
        // [X] handler
        // [X] routeParameters
        // [x] url
        // [x] method
        // [x] clientType
        // [X] routeScope
        // [X] locale
        // [x] determinedContentType
        // [X] apiVersion

        $args = [
            'argv' => ['console', '12', '--force'],
            'routeScope' => 'tenant-12',
            'locale'     => 'de_CH',
            'apiVersion' => '1.1'
        ];
        $arguments = [

        ];
        $input = $this->make($args);

        $this->assertEquals($args['argv'], $input->argv);
        $this->assertEquals($args['routeScope'], $input->routeScope);
        $this->assertInstanceOf(RouteScope::class, $input->routeScope);
        $this->assertEquals($args['locale'], $input->locale);
        $this->assertEquals($args['apiVersion'], $input->apiVersion);

        $this->assertNull($input->matchedRoute);
        $this->assertNull($input->handler);
        $this->assertSame([], $input->routeParameters);
        $this->assertInstanceOf(Url::class, $input->url);
        $this->assertEquals('console:' . $args['argv'][1], "$input->url");
        $this->assertEquals(Input::CONSOLE, $input->method);
        $this->assertEquals(Input::CLIENT_CONSOLE, $input->clientType);
        $this->assertEquals('text/x-ansi', $input->determinedContentType);

        $command = (new Command('users:index'))->argument('tenant')
            ->option('force');
        $route = new Route('GET', 'users', '');
        $route->command($command);

        /** @var ArgvInput $routed */
        $handler = function () {};
        $routeParameters = [3];
        $routed = $input->makeRouted($route, $handler, $routeParameters);

        $this->assertSame($route, $routed->matchedRoute);
        $this->assertSame($routeParameters, $routed->routeParameters);
        $this->assertSame($handler, $routed->handler);
        $this->assertSame($input->url, $routed->url);

        $this->assertEquals($args['argv'], $routed->argv);
        $this->assertEquals($args['routeScope'], $routed->routeScope);
        $this->assertEquals($args['locale'], $routed->locale);
        $this->assertEquals($args['apiVersion'], $routed->apiVersion);

        $this->assertEquals(Input::CONSOLE, $routed->method);
        $this->assertEquals(Input::CLIENT_CONSOLE, $routed->clientType);

        $this->assertEquals($args['argv'][1], $routed->arguments['tenant']);
        $this->assertTrue($routed->options['force']);

        $fork = $routed->withRouteScope('tenant-44');
        $this->assertNotSame($fork, $routed);
        $this->assertSame($route, $fork->getMatchedRoute());
        $this->assertInstanceOf(RouteScope::class, $fork->getRouteScope());
        $this->assertEquals('tenant-44', $fork->getRouteScope());




    }

    /**
     * @param array $argv
     * @return ArgvInput
     */
    public function make(array $argv=[])
    {
        return new ArgvInput($argv);
    }
}