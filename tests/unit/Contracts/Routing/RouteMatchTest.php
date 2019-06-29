<?php
/**
 *  * Created by mtils on 23.06.19 at 08:32.
 **/

namespace Ems\Contracts\Routing;


use Ems\Contracts\Core\ArrayData;
use Ems\Contracts\Core\Url as UrlContract;
use Ems\Core\Url;
use Ems\TestCase;

class RouteMatchTest extends TestCase
{
    /**
     * @test
     */
    public function it_implements_interface()
    {
        $this->assertInstanceOf(RouteMatch::class, $this->newMatch());
        $this->assertInstanceOf(ArrayData::class, $this->newMatch());
    }

    /**
     * @test
     */
    public function get_route_method_url_and_parameters()
    {
        $route = new Route('POST', 'users/{user}/addresses/{address}/edit', 'UserAddressController::edit');
        $url = new Url('https://my-test-host.my-domain.de/users/45/addresses/245/edit');
        $parameters = [
            'user'    => 45,
            'address' => 245
        ];

        $match = $this->newMatch($route, $route->methods[0], $url, $parameters);

        $this->assertSame($route, $match->route);
        $this->assertSame($url, $match->url);
        $this->assertEquals($route->methods[0], $match->method);
        $this->assertEquals($parameters, $match->parameters);
    }

    /**
     * @test
     */
    public function array_access()
    {
        $route = new Route('POST', 'users/{user}/addresses/{address}/edit', 'UserAddressController::edit');
        $url = new Url('https://my-test-host.my-domain.de/users/45/addresses/245/edit');
        $parameters = [
            'user'    => 45,
            'address' => 245
        ];

        $match = $this->newMatch($route, $route->methods[0], $url, $parameters);

        $this->assertEquals($parameters['user'], $match['user']);
    }

    /**
     * @test
     */
    public function toArray_returns_route_parameters()
    {
        $route = new Route('POST', 'users/{user}/addresses/{address}/edit', 'UserAddressController::edit');
        $url = new Url('https://my-test-host.my-domain.de/users/45/addresses/245/edit');
        $parameters = [
            'user'    => 45,
            'address' => 245
        ];

        $match = $this->newMatch($route, $route->methods[0], $url, $parameters);

        $array = $match->toArray();
        $this->assertSame($route, $array['route']);
        $this->assertSame($url, $array['url']);
        $this->assertSame($route->methods[0], $array['method']);
        $this->assertEquals($parameters, $array['parameters']);

    }

    protected function newMatch(Route $route=null, $method='GET', UrlContract $url=null, array $parameters=[])
    {
        $route = $route ?: new Route($method, '/');
        $url = $url ?: new Url('https://web-utils.de');
        return new RouteMatch($route, $method, $url, $parameters);
    }
}