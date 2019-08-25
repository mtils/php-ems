<?php
/**
 *  * Created by mtils on 24.08.19 at 07:37.
 **/

namespace Ems\Contracts\Core;

/**
 * Interface Message
 *
 * A message is a value object to send from an emitter to a listener
 * (handler/receiver). Typically in an EMS application you should not user event
 * objects but rather connecting signatures.
 * But in some cases you want to have a defined way to share messages in your
 * application.
 * In this case use this interface. The interface is basically an array.
 *
 * @package Ems\Contracts\Core
 */
interface Message extends ArrayData
{
    /**
     * Type input
     */
    const INPUT = 'input';

    /**
     * Type output
     */
    const OUTPUT = 'output';

    /**
     * Type log
     */
    const LOG = 'log';

    /**
     * Type custom
     */
    const CUSTOM = 'custom';

    /**
     * Source network
     */
    const NETWORK = 'network';

    /**
     * Source terminal (tty)
     */
    const TERMINAL = 'terminal';

    /**
     * Inter Process Communication Source
     */
    const IPC = 'pic';

    /**
     * Source is just another class inside the same application
     */
    const INTERNAL = 'internal';

    /**
     * Return the type of Message this is. A http request or console input would
     * be INPUT.
     *
     * @return string
     *
     * @see self::INPUT
     */
    public function type();

    /**
     * Return true if the input was accepted. If nobody accepted it this returns
     * false even it was not ignored.
     *
     * @return bool
     */
    public function isAccepted();

    /**
     * Return true if the input was ignored. If nobody ignored it this returns
     * false even it was not accepted.
     *
     * @return bool|null
     */
    public function isIgnored();

    /**
     * Mark the message as accepted.
     *
     * @return self
     */
    public function accept();

    /**
     * Mark the message as ignored.
     *
     * @return self
     */
    public function ignore();

    /**
     * Through which channel that was sent?
     *
     * @return string
     *
     * @see self::NETWORK
     */
    public function source();

}