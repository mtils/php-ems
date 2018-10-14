<?php
/**
 *  * Created by mtils on 19.08.18 at 13:22.
 **/

namespace Ems\Routing;


use Ems\Contracts\Core\Input;
use Ems\Contracts\Core\SupportsCustomFactory;
use Ems\Contracts\Routing\RouteCollection;
use Ems\Contracts\Routing\RouteMatch;
use Ems\Contracts\Routing\RouteMatcher;
use Ems\Contracts\Routing\Router as RouterContract;
use Ems\Core\Support\CustomFactorySupport;

class Router implements RouterContract, SupportsCustomFactory
{
    use CustomFactorySupport;

    /**
     * @var RouteCollection
     */
    protected $routes;

    /**
     * @var RouteMatcher
     */
    protected $matcher;

    /**
     * @var callable
     */
    protected $routableProvider;

    /**
     * @param Input $input
     * @return mixed
     */
    public function handle(Input $input)
    {
        $routes = $this->routes->getByClientType($input->clientType())
                               ->getByScope($input->routeScope());

        $routeMatch = $this->matcher->match($input->method(), $input->url(), $routes);

        return $this->runRoute($routeMatch, $input);
    }

    /**
     * @return RouteCollection
     */
    public function routes()
    {
        return $this->routes;
    }


    public function __invoke(Input $input, callable $next)
    {
        return $this->handle($input);
    }


    public function provideRoutableBy(callable $provider)
    {
        $this->routableProvider = $provider;
        return $this;
    }

    protected function runRoute(RouteMatch $match, Input $input)
    {
        $action = $this->createObject($match->route->handler);
        return $this->_customFactory->call($action, [$match, $input]);
    }


}