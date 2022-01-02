<?php
/**
 *  * Created by mtils on 24.08.19 at 08:28.
 **/

namespace Ems\Contracts\Skeleton;

use Ems\Contracts\Core\Connection;
use Ems\Contracts\Routing\Input;

/**
 * Interface InputConnection
 *
 * This represents basically php://stdin.
 *
 * @package Ems\Contracts\Core
 */
interface InputConnection extends Connection
{
    /**
     * Is this connection open for further requests? In case of console, daemons
     * or pipes possibly yes. In http requests typically no.
     *
     * @return bool
     */
    public function isInteractive();

    /**
     * Receive the input. Use the returned input to process one. Pass a callable
     * to "stream receive" input.
     *
     * @param callable|null $into
     *
     * @return Input
     */
    public function read(callable $into=null);
}