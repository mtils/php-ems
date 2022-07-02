<?php
/**
 *  * Created by mtils on 11.08.19 at 08:00.
 **/

namespace Ems;

use Ems\Contracts\Core\Url as UrlContract;
use Ems\Contracts\Routing\Input;
use Ems\Contracts\Routing\RouteCollector;
use Ems\Contracts\Routing\Router as RouterContract;
use Ems\Routing\RouteRegistry;
use Ems\Core\Url;
use Ems\Routing\GenericInput;
use Ems\Routing\Router;

use function is_string;
use function str_replace;


trait RoutingTrait
{
    use TestData;
    protected static $testRoutes;

    /**
     * @param bool $filled
     * @return Router
     */
    protected function router(bool $filled=false) : Router
    {
        $router = new Router();
        if ($filled) {
            $this->fill($router);
        }
        return $router;
    }

    protected function registry(bool $filled=false) : RouteRegistry
    {
        $registry = new RouteRegistry();
        if ($filled) {
            $this->fill($registry);
        }
        return $registry;
    }

    /**
     * @beforeClass
     */
    public static function loadTestRoutes()
    {
        static::$testRoutes = static::includeDataFile('routing/basic-routes.php');
    }

    protected function fillIfNotFilled($registry, array $controllerReplace=[])
    {
        if (!$registry->getByPattern('users')) {
            $this->fill($registry, $controllerReplace);
        }
    }

    protected function fill($registry, $controllerReplace=[])
    {
        $registry->register(function (RouteCollector $collector) use ($controllerReplace) {
            $this->fillCollector($collector, $controllerReplace);
        });
    }

    protected function fillCollector(RouteCollector $collector, $controllerReplace=[])
    {
        foreach (static::$testRoutes as $routeData) {
            $handler = $routeData['handler'];
            if ($controllerReplace && is_string($handler)) {
                $handler = $this->replaceControllerName($handler, $controllerReplace);
            }
            $collector->on($routeData['method'], $routeData['pattern'], $handler)
                ->name($routeData['name']);
        }
    }

    /**
     * @param string $handler
     * @param array $replacements
     *
     * @return string
     */
    protected function replaceControllerName($handler, array $replacements)
    {
        foreach ($replacements as $search=>$replace) {
            $handler = str_replace($search, $replace, $handler);
        }
        return $handler;
    }

    /**
     * @param UrlContract|string $url
     * @param string $method
     * @param string $clientType
     * @param string $scope
     *
     * @return Input
     */
    protected function routable($url, string $method=Input::GET, string $clientType=Input::CLIENT_WEB, string $scope='default') : Input
    {
        $routable = new GenericInput();
        if (!$url instanceof UrlContract) {
            $url = new Url($url);
        }
        return $routable->setMethod($method)->setUrl($url)->setClientType($clientType)->setRouteScope($scope);
    }
}