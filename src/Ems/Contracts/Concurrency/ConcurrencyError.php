<?php
/**
 *  * Created by mtils on 08.09.19 at 07:37.
 **/

namespace Ems\Contracts\Concurrency;


use Throwable;

/**
 * Interface ConcurrencyError
 *
 * This is an interface for all concurrency exceptions.
 * It holds the result of your callable in run(callable). To get any exceptions
 * of your callable just use getPrevious().
 *
 * @package Ems\Contracts\Concurrency
 */
interface ConcurrencyError
{
    //
}