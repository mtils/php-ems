<?php
/**
 *  * Created by mtils on 15.09.19 at 10:33.
 **/

namespace Ems\Contracts\Routing;


/**
 * Class Option
 *
 * In option is a parameter passed to a console command with one or two minus
 * console assets:copy --recursive -v --max=3
 *
 * @package Ems\Contracts\Routing
 */
class Option extends ConsoleParameter
{
    /**
     * A shortcut to allow fast access.
     *
     * @var string
     */
    public $shortcut ='';
}