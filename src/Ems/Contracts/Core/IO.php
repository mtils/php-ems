<?php
/**
 *  * Created by mtils on 24.08.19 at 08:56.
 **/

namespace Ems\Contracts\Core;

use Psr\Log\LoggerInterface;

/**
 * Interface IO
 *
 * This represents the central place to read (requests/input)m write (output|echo)
 * and log.
 *
 * @package Ems\Contracts\Core
 */
interface IO
{
    /**
     * @return InputConnection
     */
    public function in();

    /**
     * @return OutputConnection
     */
    public function out();

    /**
     * @return LoggerInterface
     */
    public function log();
}