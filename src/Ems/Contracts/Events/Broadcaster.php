<?php
/**
 * Created by PhpStorm.
 * User: michi
 * Date: 10.11.17
 * Time: 13:15
 */

namespace Ems\Contracts\Events;

use Ems\Contracts\Core\Url;

interface Broadcaster
{
    /**
     * Return a name for this broadcaster.
     *
     * @return string
     */
    public function name();

    /**
     * Returns a name for api this Broadcaster uses
     *
     * @return string
     **/
    public function apiName();

    /**
     * Return a version string.
     *
     * @return string
     */
    public function apiVersion();

    /**
     * Make a event publicly available
     *
     * @param string|Signal $event
     * @param array|XType   $parameters (optional)
     * @param string        $description (optional)
     *
     * @return Signal
     **/
    public function addSignal($event, $parameters=null, $description=null);

    /**
     * Return an array of signal objects for all announced events.
     *
     * @return Signal[]
     **/
    public function signals();

    /**
     * Return a known Signal.
     *
     * @param string $name
     *
     * @return Signal
     *
     * @throws \Ems\Contracts\Core\Errors\NotFound
     */
    public function getSignal($name);

    /**
     * Returns all receivers which were added to $signalName.
     *
     * @param string $signalName
     *
     * @return Receiver[]
     **/
    public function receivers($signalName);

    /**
     * Add a new Receiver for $signalName
     *
     * @param string|Receiver $signalOrReceiver
     * @param Url             $url (optional)
     * @param string          $description (optional)
     *
     * @return Receiver
     **/
    public function addReceiver($signalOrReceiver, Url $url=null, $description=null);

    /**
     * Remove a (previously added) receiver.
     *
     * @param Receiver $receiver
     *
     * @return self
     */
    public function removeReceiver(Receiver $receiver);

    /**
     * Return a known Receiver.
     *
     * @param mixed $id
     *
     * @return Receiver
     *
     * @throws \Ems\Contracts\Core\Errors\NotFound
     */
    public function getReceiver($id);

    /**
     * Opens a slot to push into.
     *
     * @param string|Slot                      $eventName
     * @param array|\Ems\Contracts\XType\XType $parameters (optional)
     * @param string                           $name (optional)
     *
     * @return Slot
     **/
    public function addSlot($eventName, $parameters=null, $name=null);

    /**
     * Return all opened slots.
     *
     * @return Slot[]
     **/
    public function slots();

    /**
     * Return a known Slot.
     *
     * @param string $name
     *
     * @return Slot
     *
     * @throws \Ems\Contracts\Core\Errors\NotFound
     */
    public function getSlot($name);

    /**
     * Perform a slot call. You have to do the implementation specific stuff
     * outside. So for example you need a controller to receive the request,
     * cast the stuff and then give it to that method.
     * The $eventName of that slot will then marked and sent through the event
     * bus.
     * The $parameters MUST be $key=>$value. Not indexed.
     *
     * @param string $slotName
     * @param array  $parameters (optional)
     *
     * @return Slot
     */
    public function receive($slotName, array $parameters=[]);

    /**
     * Test a signal. This is not for production usage. Normally the broadcaster
     * receives events from an added event bus. But to trigger the signals
     * manually this method is your friend.
     * The $parameters MUST be $key=>$value. Not indexed.
     *
     * @param string $signalName
     * @param array  $parameters (optional)
     *
     * @return mixed
     */
    public function signalize($signalName, array $parameters=[]);
}