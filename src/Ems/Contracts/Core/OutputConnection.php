<?php
/**
 *  * Created by mtils on 24.08.19 at 08:32.
 **/

namespace Ems\Contracts\Core;

/**
 * Interface OutputConnection
 *
 * Use this to output something (in a file, console, to http server,...)
 *
 * @package Ems\Contracts\Core
 */
interface OutputConnection extends Connection
{
    /**
     * Write the output. Usually just echo it
     *
     * @param string|Stringable $output
     * @param bool $lock
     *
     * @return mixed
     */
    public function write($output, $lock=false);
}