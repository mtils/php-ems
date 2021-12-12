<?php
/**
 *  * Created by mtils on 21.07.19 at 14:15.
 **/

namespace Ems\Contracts\Routing;

use Ems\Contracts\Core\Input;
use Ems\Contracts\Core\Response;

/**
 * Interface InputHandler
 *
 * This is just a placeholder interface. From my point of view there is nothing
 * more needed to handle input then just one method (a Closure). So this interface
 * is just a helper to store the binding in an IOCContainer and to mark that it
 * (should) return a response.
 *
 * @package Ems\Contracts\Routing
 */
interface InputHandler
{
    /**
     * Handle the input and return a corresponding
     *
     * @param Input $input
     *
     * @return Response
     */
    public function __invoke(Input $input);

}