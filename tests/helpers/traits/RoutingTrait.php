<?php
/**
 *  * Created by mtils on 11.08.19 at 08:00.
 **/

namespace Ems;

use Ems\Contracts\Routing\RouteCollector;
use Ems\Contracts\Routing\Router as RouterContract;
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

    /**
     * @beforeClass
     */
    public static function loadTestRoutes()
    {
        static::$testRoutes = static::includeDataFile('routing/basic-routes.php');
    }

    protected function fillIfNotFilled(RouterContract $router, array $controllerReplace=[])
    {
        if (!$router->getByPattern('users')) {
            $this->fill($router, $controllerReplace);
        }
    }

    protected function fill(RouterContract $router, $controllerReplace=[])
    {
        $router->register(function (RouteCollector $collector) use ($controllerReplace) {
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
}