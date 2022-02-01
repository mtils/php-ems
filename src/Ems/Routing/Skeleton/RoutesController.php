<?php
/**
 *  * Created by mtils on 01.02.2022 at 18:36.
 **/

namespace Ems\Routing\Skeleton;

use Ems\Contracts\Routing\Route;
use Ems\Contracts\Routing\Router;

use function implode;

class RoutesController
{
    public function index(Router $router)
    {
        /** @var Route $route */
        foreach ($router as $route) {
            echo "\n" . implode(',',$route->methods) . " $route->pattern $route->name " . implode(',',$route->clientTypes);
        }

        return "\nBye";
    }
}