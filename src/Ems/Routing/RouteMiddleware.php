<?php
/**
 *  * Created by mtils on 11.08.19 at 11:53.
 **/

namespace Ems\Routing;

use Ems\Contracts\Core\Exceptions\Termination;
use Ems\Contracts\Core\Input;
use Ems\Contracts\Core\SupportsCustomFactory;
use Ems\Contracts\Routing\MiddlewareCollection as MiddlewareCollectionContract;
use Ems\Core\Exceptions\HandlerNotFoundException;
use Ems\Core\Exceptions\UnConfiguredException;
use Ems\Core\Support\CustomFactorySupport;
use Ems\Expression\ConstraintParsingMethods;

/**
 * Class RouteMiddleware
 *
 * This class runs any per route middleware (that was assigned by Route::middleware().
 *
 * @package Ems\Routing
 */
class RouteMiddleware implements SupportsCustomFactory
{
    use CustomFactorySupport;
    use ConstraintParsingMethods;

    /**
     * @var MiddlewareCollectionContract
     */
    protected $middlewareCollection;

    public function __construct(MiddlewareCollectionContract $middlewareCollection=null)
    {
        $this->middlewareCollection = $middlewareCollection ?: new MiddlewareCollection();
    }

    public function __invoke(Input $input, callable $next)
    {
        if (!$input->isRouted()) {
            throw new UnConfiguredException('The input has to be routed to get handled by ' . static::class . '.');
        }

        $route = $input->matchedRoute();

        $middlewares = $route->middlewares;

        if (!$middlewares) {
            return $next($input);
        }

        $this->configureCollection($middlewares);

        try {
            return $this->middlewareCollection->__invoke($input);
        } catch (Termination $termination) {
            return $next($input);
        }
//        catch (HandlerNotFoundException $e) {
//            return $next($input);
//        }

    }

    protected function configureCollection(array $middlewares)
    {
        $this->middlewareCollection->clear();
        foreach ($middlewares as $middlewareCommand) {
            $constraints = $this->parseConstraint($middlewareCommand);
            foreach ($constraints as $middlewareName=>$parameters) {
                $this->middlewareCollection->add($middlewareCommand, $middlewareName, $parameters);
            }
        }

        $this->middlewareCollection->add('termination', function (Input $input, callable $next) {
            try {
                if ($response = $next($input)) {
                    return $response;
                }
            } catch (HandlerNotFoundException $e) {
                //
            }
            throw new Termination();
        });
    }

    /**
     * No normalizing needed here.
     *
     * @param string $name
     *
     * @return string
     **/
    protected function normalizeConstraintName($name)
    {
        return $name;
    }

}