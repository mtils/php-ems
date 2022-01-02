<?php
/**
 *  * Created by mtils on 02.12.2021 at 19:46.
 **/

namespace Ems\Contracts\Routing;

/**
 * This is a helper interface that marks a class to be "dependent" on a request.
 * Classes like controllers or ResponseFactory always depends on input. To reduce
 * the need to pass the request through every class this interface is used.
 *
 */
interface UtilizesInput
{
    /**
     * @param Input $input
     * @return void
     */
    public function setInput(Input $input);
}