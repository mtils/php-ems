<?php
/**
 *  * Created by mtils on 30.06.19 at 10:54.
 **/

namespace Ems\Contracts\Routing;

interface Router
{
    /**
     * The key for passing common middleware attributes in register().
     * Passing middleware per route should add them to common middleware.
     */
    const MIDDLEWARE = 'middleware';

    /**
     * The key for passing common client type attributes in register().
     * Passing client types per route should replace the common ones.
     */
    const CLIENT = 'client';

    /**
     * The key for passing common route scope attributes in register().
     * Passing scopes per route should replace the common ones.
     */
    const SCOPE = 'scope';

    /**
     * The key for passing a common path prefix attributes in register().
     * The common prefix will be (fully) added before the path for each route.
     */
    const PREFIX = 'prefix';

    /**
     * The key for passing a common handler class prefix attributes in register().
     * The common prefix will be added before the handler string of each route.
     * So you could add UserController::class as a controller and then only
     * write edit, create,... in your routes.
     */
    const CONTROLLER = 'controller';

    /**
     * Make the routable routed. At the end find a route and a handler, assign it
     * to the passed routable. It returns the result of Input::makeRouted() so
     * depending on the input you will get a new instance. (immutable input)
     *
     * @param Input $routable
     *
     * @return Input
     */
    public function route(Input $routable) : Input;

    /**
     * Return the dispatcher for $clientType. This is needed for the UrlGenerator
     * to compile the urls by the dispatcher (who should now his urls) and compilation
     * of routes by a cached proxy.
     *
     * @param string $clientType
     *
     * @return Dispatcher
     */
    public function getDispatcher(string $clientType) : Dispatcher;

}