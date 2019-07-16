<?php
/**
 *  * Created by mtils on 19.08.18 at 13:21.
 **/

namespace Ems\Contracts\Routing;


use Ems\Contracts\Core\HasMethodHooks;
use Ems\Contracts\Core\Input;
use Ems\Contracts\Core\Response;

/**
 * Interface Router
 *
 * The router is responsible for finding a handler and run the handler and give
 * the result back.
 * The HasMethodHooks Interface is to allow to hook into every phase of the
 * process. So you can await that methodHooks() will return at least every phase
 * const to allow all kinds of manipulation.
 *
 * @package Ems\Contracts\Routing
 */
interface Router extends HasMethodHooks
{

    /**
     * The first phase, find a route.
     *
     * @var string
     */
    const PHASE_DISPATCH = 'dispatch';

    /**
     * Make the input "routed". This means just assign the matched route to the
     * request
     */
    const PHASE_ROUTE = 'route';

    /**
     * The second phase, instantiate the handler.
     *
     * @var string
     */
    const PHASE_INSTANTIATE = 'instantiate';

    /**
     * The third phase, call the handler.
     *
     * @var string
     */
    const PHASE_CALL = 'call';

    /**
     * The fourth phase, make the result a response and give it back.
     *
     * @var string
     */
    const PHASE_RESPOND = 'respond';

    /**
     * @param Input $input
     *
     * @return Response
     */
    public function handle(Input $input);

    /**
     * @return Route[]
     */
    public function routes();
}