<?php
/**
 * Created by PhpStorm.
 * User: michi
 * Date: 10.11.17
 * Time: 13:07
 */

namespace Ems\Contracts\Events;

use Ems\Contracts\Core\Named;

/**
 * Interface Receiver
 *
 * A receiver represents one added listener to a Signal. So if you have
 * a Signal called "user.updated" you could add a receiver for that signal.
 * Every receiver has an url, this url points to a slot.
 * Every time the Signal will be emitted, the Receivers of that slot get
 * informed.
 * In opposite to the Signal and Slot objects this one here is not exposed
 * by code, its an added object which should be stored somewhere.
 *
 * @package Ems\Contracts\Events
 */
interface Receiver extends Named
{

    /**
     * Return the signal name on which this receiver is connected.
     *
     * @return string
     */
    public function getSignalName();

    /**
     * Return the url, where this Receiver can be pushed.
     *
     * @return \Ems\Contracts\Core\Url
     */
    public function getUrl();
}