<?php
/**
 *  * Created by mtils on 06.02.2022 at 15:13.
 **/

namespace Ems\Skeleton\Routing;

use Ems\Console\AnsiRenderer;
use Ems\Contracts\Core\Exceptions\TypeException;
use Ems\Contracts\Core\Type;
use Ems\Contracts\Routing\Route;
use Ems\Contracts\Routing\RouteCollector;
use Ems\Routing\RouteSearch;
use OutOfRangeException;
use Traversable;
use UnexpectedValueException;

use function explode;
use function implode;
use function strpos;

class RoutesConsoleView
{
    public function __invoke(string $view, array $vars, AnsiRenderer $renderer)
    {
        if (!isset($vars['routes'])) {
            throw new OutOfRangeException('Missing key "routes" in view variables.');
        }
        if (!isset($vars['keys'])) {
            throw new OutOfRangeException('Missing key "keys" in view variables.');
        }

        $routes = $vars['routes'];

        if (!Type::is($routes, Traversable::class)) {
            throw new TypeException("Routes have to be traversable");
        }

        $rows = [];
        /** @var Route $route */
        foreach ($routes as $route) {
            $row = [];
            foreach ($vars['keys'] as $key) {
                $row[] = $this->getValue($route, $key);
            }
            $rows[] = $row;
        }

        return $renderer->format($renderer->table($rows, $vars['keys'])) . "\n";
    }

    /**
     * @param Route $route
     * @param string $key
     * @return string
     */
    protected function getValue(Route $route, string $key) : string
    {
        switch ($key) {
            case RouteSearch::METHODS:
                return implode(',',$route->methods);
            case RouteSearch::PATTERN:
                return $route->pattern;
            case RouteSearch::NAME:
                return $route->name;
            case RouteSearch::CLIENTS:
                return implode(',',$route->clientTypes);
            case RouteSearch::SCOPES:
                return implode(',', $route->scopes);
            case RouteSearch::MIDDLEWARE:
                $middlewares = [];
                foreach ($this->parseMiddleware($route) as $middleware=>$parameters) {
                    $middlewares[] = $parameters ? ($middleware . ':' . $parameters) : $middleware;
                }
                return implode(',', $middlewares);
        }
        throw new UnexpectedValueException("Unknown key $key. I only know " . implode(',', RouteSearch::ALL_KEYS));
    }

    protected function parseMiddleware(Route $route) : array
    {
        if (!$route->middlewares) {
            return [];
        }

        $parsed = [];
        foreach ($route->middlewares as $middleware) {
            if (!strpos($middleware, RouteCollector::$middlewareDelimiter)) {
                $parsed[Type::short($middleware)] = '';
                continue;
            }
            list($name, $parameters) = explode(RouteCollector::$middlewareDelimiter, $middleware, 2);
            $parsed[Type::short($name)] = $parameters;
        }

        return $parsed;
    }
}