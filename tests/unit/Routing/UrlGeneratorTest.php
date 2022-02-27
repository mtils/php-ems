<?php
/**
 *  * Created by mtils on 27.02.2022 at 07:57.
 **/

namespace Ems\Routing;

use Closure;
use Ems\Contracts\Core\Url;
use Ems\Contracts\Routing\Input;
use Ems\Contracts\Routing\RouteScope;
use Ems\RoutingTrait;
use Ems\TestCase;
use Ems\Contracts\Routing\UrlGenerator as UrlGeneratorContract;

class UrlGeneratorTest extends TestCase
{
    use RoutingTrait;

    /**
     * @test
     */
    public function it_implements_interface()
    {
        $this->assertInstanceOf(UrlGeneratorContract::class, $this->make());
    }

    /**
     * @test
     */
    public function to_returns_path_plus_url()
    {
        $urls = $this->make();
        $domain = 'https://web-utils.de';
        $provider = $this->urlProvider($domain);
        $urls->setBaseUrlProvider($provider);

        $myData = $urls->to('my/data');
        $this->assertInstanceOf(Url::class, $myData);
        $this->assertEquals("$domain/my/data", "$myData");

        $home = $urls->to('/');
        $this->assertInstanceOf(Url::class, $home);
        $this->assertEquals("$domain", "$home");

    }

    /**
     * @test
     */
    public function to_returns_path_on_different_scope()
    {
        $urls = $this->make();
        $domain = 'https://web-utils.de';
        $provider = $this->urlProvider($domain);
        $urls->setBaseUrlProvider($provider);

        $myData = $urls->to('my/data', 'admin');

        $this->assertInstanceOf(Url::class, $myData);
        $this->assertEquals("https://admin.web-utils.de/my/data", "$myData");

        $home = $urls->to('/', 'en');
        $this->assertInstanceOf(Url::class, $home);
        $this->assertEquals("https://en.web-utils.de", "$home");

    }

    protected function make(Router $router=null, CurlyBraceRouteCompiler $compiler=null, Input $input=null, &$baseUrlCache=[]) : UrlGenerator
    {
        $router = $router ?: $this->router();
        $compiler = $compiler ?: new CurlyBraceRouteCompiler();
        return new UrlGenerator($router, $compiler, $input, $baseUrlCache);
    }


    protected function urlProvider($baseUrl = 'http://localhost') : Closure
    {
        return function (Input $input, $scope=null) use ($baseUrl) {
            $baseUrl = $baseUrl instanceof Url ? $baseUrl : new \Ems\Core\Url($baseUrl);
            if ($scope === null) {
                return $baseUrl;
            }
            return $baseUrl->host("$scope.$baseUrl->host");
        };
    }
}