<?php
/**
 * Created by PhpStorm.
 * User: michi
 * Date: 11.11.17
 * Time: 09:20
 */

namespace Ems\Events;

use Ems\Contracts\Core\Url as UrlContract;
use Ems\Contracts\Events\Broadcaster;
use Ems\Contracts\Events\Bus as BusContract;
use Ems\Contracts\Core\HasMethodHooks;
use Ems\Contracts\Events\Message;
use Ems\Contracts\Events\Receiver;
use Ems\Contracts\Events\Signal;
use Ems\Contracts\Events\Slot;
use Ems\Contracts\XType\XType;
use Ems\Contracts\Http\Client as ClientContract;
use Ems\Core\Exceptions\ConstraintViolationException;
use Ems\Core\Exceptions\ResourceNotFoundException;
use Ems\Core\Exceptions\UnConfiguredException;
use Ems\Core\Helper;
Use Ems\Core\Patterns\HookableTrait;
use Ems\Core\Url;


class RESTBroadcaster implements Broadcaster, HasMethodHooks
{
    use HookableTrait;

    /**
     * @var array
     */
    protected $signals = [];

    /**
     * @var array
     */
    protected $signalsByEventName = [];

    /**
     * @var array
     */
    protected $slots = [];

    /**
     * @var array
     */
    protected $receivers = [];

    /**
     * @var UrlContract
     */
    protected $baseUrl;

    /**
     * @var UrlContract
     */
    protected $signalsUrl;

    /**
     * @var UrlContract
     */
    protected $slotsUrl;

    /**
     * @var string
     */
    protected $slotSegment = 'calls';

    /**
     * @var BusContract
     */
    protected $bus;

    /**A
     * @var ClientContract
     */
    protected $client;

    /**
     * @var string
     */
    protected $contentType = 'application/json';

    /**
     * RESTBroadcaster constructor.
     *
     * @param BusContract    $bus
     * @param ClientContract $client
     */
    public function __construct(BusContract $bus, ClientContract $client)
    {
        $this->bus = $bus;
        $this->client = $client;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function name()
    {
        return 'ems.rest';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function apiName()
    {
        return 'ems.event.broadcast.rest';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function apiVersion()
    {
        return '0.1';
    }

    /**
     * @inheritDoc
     *
     * @param string|Signal $event
     * @param array|XType   $parameters (optional)
     * @param string        $description (optional)
     *
     * @return Signal
     **/
    public function addSignal($event, $parameters = null, $description = null)
    {

        $signal = $event instanceof Signal ? $event : new Signal(['eventName' => $event]);

        if (isset($this->signals[$signal->name])) {
            throw new \OverflowException("A signal named '{$signal->name}' was already added.");
        }

        if ($parameters) {
            $signal->parameters = $parameters;
        }

        if ($description) {
            $signal->description = $description;
        }

        $signal->url = $this->signalUrl($signal);

        $this->signals[$signal->name] = $signal;

        if (!isset($this->signalsByEventName[$signal->eventName])) {
            $this->signalsByEventName[$signal->eventName] = [];
        }

        $this->signalsByEventName[$signal->eventName][] = $signal;

        $this->bus->when('!no-broadcast')->on($signal->eventName, function () use ($signal) {
            $this->signalize($signal->name, $this->namedParameters($signal, func_get_args()));
        });

        return $signal;

    }

    /**
     * @inheritDoc
     *
     * @return Signal[]
     **/
    public function signals()
    {
        return array_values($this->signals);
    }

    /**
     * @inheritdoc
     *
     * @param string $name
     *
     * @return Signal
     *
     * @throws ResourceNotFoundException
     */
    public function getSignal($name)
    {
        if (!isset($this->signals[$name])) {
            throw new ResourceNotFoundException("Signal $name not found");
        }

        return $this->signals[$name];
    }

    /**
     * @inheritDoc
     *
     * @param string $signalName
     *
     * @return Receiver[]
     **/
    public function receivers($signalName)
    {

        $this->getSignal($signalName);

        $receivers = [];

        foreach ($this->receivers as $id=>$receiver) {
            if ($receiver->getSignalName() == $signalName) {
                $receivers[] = $receiver;
            }
        }

        return $receivers;

    }

    /**
     * Set the receivers (indexed by its id)
     *
     * @param array|\ArrayAccess $receivers
     *
     * @return self
     */
    public function setReceivers($receivers)
    {
        Helper::forceArrayAccess($receivers);
        $this->receivers = $receivers;
        return $this;
    }

    /**
     * @inheritDoc
     *
     * @param string|Receiver $signalOrReceiver
     * @param Url             $url (optional)
     * @param string          $description (optional)
     *
     * @return Receiver
     **/
    public function addReceiver($signalOrReceiver, UrlContract $url=null, $name=null)
    {
        $receiver = $signalOrReceiver instanceof Receiver ? $signalOrReceiver : new GenericReceiver($signalOrReceiver, $url);

        if ($name) {
            $receiver->setName($name);
        }

        $signal = $this->getSignal($receiver->getSignalName());

        if ($this->signalHasReceiverUrl($signal->name, $receiver->getUrl())) {
            $url = $receiver->getUrl();
            throw new ConstraintViolationException("A receiver with url '$url' was already added to signal '$signal->name'");
        }

        $myUrl = $this->getSlotsUrl()->append($signal->name)->append('calls');

        // If it is the own slot url
        if ($myUrl->equals($receiver->getUrl(), ['user', 'host', 'path'])) {
            throw new ConstraintViolationException("You cannot add a receiver with the slot url '$url' on this server.");
        }

        $this->receivers[$receiver->getId()] = $receiver;

        return $receiver;

    }

    /**
     * @inheritDoc
     *
     * @param Receiver $receiver
     *
     * @return self
     */
    public function removeReceiver(Receiver $receiver)
    {
        if (isset($this->receivers[$receiver->getId()])) {
            unset($this->receivers[$receiver->getId()]);
            return $this;
        }

        throw new ResourceNotFoundException('Receiver #' . $receiver->getId() . ' not found.');
    }

    /**
     * @inheritdoc
     *
     * @param mixed $id
     *
     * @return Receiver
     *
     * @throws ResourceNotFoundException
     */
    public function getReceiver($id)
    {
        if (!isset($this->receivers[$id])) {
            throw new ResourceNotFoundException("Receiver #$id not found");
        }
        return $this->receivers[$id];
    }

    /**
     * @inheritDoc
     *
     * @param string|Slot $eventName
     * @param array|XType $parameters (optional)
     * @param string      $name (optional)
     *
     * @return Slot
     **/
    public function addSlot($eventName, $parameters = null, $description = null)
    {
        $slot = $eventName instanceof Slot ? $eventName : new Slot(['eventName' => $eventName]);

        if (isset($this->slots[$slot->name])) {
            throw new \OverflowException("A slot named '{$slot->name}' was already added.");
        }

        if ($parameters) {
            $slot->parameters = $parameters;
        }

        if ($description) {
            $slot->description = $description;
        }

        $slot->name = $this->messageName($slot->name);

        $slot->url = $this->slotUrl($slot);

        $this->slots[$slot->name] = $slot;

        return $slot;
    }

    /**
     * @inheritDoc
     *
     * @return Slot[]
     **/
    public function slots()
    {
        return array_values($this->slots);
    }

    /**
     * Return a known Slot.
     *
     * @param string $name
     *
     * @return Slot
     *
     * @throws ResourceNotFoundException
     */
    public function getSlot($name)
    {
        if (!isset($this->slots[$name])) {
            throw new ResourceNotFoundException("Slot $name not found");
        }
        return $this->slots[$name];

    }

    /**
     * @inheritDoc
     *
     * @return Slot
     **/
    public function receive($slotName, array $parameters = [])
    {
        // Lets call that before getting the slot to catch wrong slot pushes
        $this->callBeforeListeners('receive', [$slotName, $parameters]);

        $slot = $this->getSlot($slotName);

        $this->bus->mark(['from-remote', 'no-broadcast'])
                  ->fire(
                      $slot->eventName,
                      $this->indexedParameters($slot, $parameters)
                  );

        $this->callAfterListeners('receive', [$slot, $parameters]);

        return $slot;
    }

    /**
     * @inheritDoc
     *
     * @param string $slotName
     * @param array  $parameters (optional)
     *
     * @return mixed
     */
    public function signalize($signalName, array $parameters = [])
    {

        $signal = $this->getSignal($signalName);

        foreach ($this->receivers($signal->name) as $receiver) {

            $this->callBeforeListeners('signalize', [$receiver, $signal, $parameters]);

            $response = $this->client->post($receiver->getUrl(), [
                'parameters' => $parameters
            ], $this->contentType);

            $this->callAfterListeners('signalize', [$receiver, $signal, $parameters, $response]);

        }

        return;
    }

    /**
     * Return the base url of both slots and signals. If you separately assign
     * it for signals and slots, this method will return null.
     *
     * @return UrlContract
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * Return the base url of both slots and signals. If you accept the convention
     * of /signals and /slots you can just assign a baseUrl. If not, you have
     * to set them separately with setSignalsBaseUrl() and setSlotsBaseUrl().
     *
     * @param UrlContract $url
     *
     * @return self
     */
    public function setBaseUrl(UrlContract $url)
    {
        $this->baseUrl = $url;
        return $this;
    }

    /**
     * Return the signals url
     *
     * @return UrlContract
     */
    public function getSignalsUrl()
    {
        if ($this->signalsUrl) {
            return $this->signalsUrl;
        }

        if (!$this->baseUrl) {
            throw new UnConfiguredException('No SignalsUrl and no baseUrl found to generate a SignalsUrl');
        }

        return $this->baseUrl->append('signals');
    }

    /**
     * Set a base url for all signals.
     *
     * @param UrlContract $url
     *
     * @return $this
     */
    public function setSignalsUrl(UrlContract $url)
    {
        $this->signalsUrl = $url;
        return $this;
    }

    /**
     * Return the slots base url.
     *
     * @return UrlContract
     */
    public function getSlotsUrl()
    {
        if ($this->slotsUrl) {
            return $this->slotsUrl;
        }

        if (!$this->baseUrl) {
            throw new UnConfiguredException('No SlotsUrl and no baseUrl found to generate a SlotsUrl');
        }

        return $this->baseUrl->append('slots');
    }

    /**
     * Set the slots base url.
     *
     * @param UrlContract $slotsUrl
     *
     * @return self
     */
    public function setSlotsUrl($slotsUrl)
    {
        $this->slotsUrl = $slotsUrl;
        return $this;
    }

    /**
     * @return array
     */
    public function methodHooks()
    {
        return ['signalize', 'receive'];
    }

    /**
     * Get the url to access a particular Signal.
     *
     * @param Signal $signal
     *
     * @return UrlContract
     */
    protected function signalUrl(Signal $signal)
    {
        return $this->getSignalsUrl()->append($this->segment($signal));
    }

    /**
     * Get the url to access a particular Signal.
     *
     * @param Slot $slot
     *
     * @return UrlContract
     */
    protected function slotUrl(Slot $slot)
    {
        return $this->getSlotsUrl()->append($this->segment($slot));
    }

    /**
     * Return a valid http segment for $signalName.
     *
     * @param Message $signal
     *
     * @return string
     */
    protected function segment(Message $signal)
    {
        return str_replace('/', '--', $signal->name);
    }

    /**
     * Return the message name of http $segment.
     *
     * @param string $segment
     *
     * @return string
     */
    protected function messageName($segment)
    {
        return str_replace('--', '/', $segment);
    }

    /**
     * Make the fired indexed parameters to
     *
     * @param Signal $signal
     * @param array $indexed
     *
     * @return array
     */
    protected function namedParameters(Signal $signal, array $indexed)
    {
        $definition = $signal->parameters;
        $names = isset($definition[0]) ? $definition : array_keys($definition);

        $named = [];
        foreach ($names as $i=>$name) {
            $named[$name] = isset($indexed[$i]) ? $indexed[$i] : null;
        }

        return $named;
    }

    /**
     * Make the fired indexed parameters to
     *
     * @param Slot  $slot
     * @param array $named
     *
     * @return array
     */
    protected function indexedParameters(Slot $slot, array $named)
    {
        $definition = $slot->parameters;
        $names = isset($definition[0]) ? $definition : array_keys($definition);

        $indexed = [];
        foreach ($names as $i=>$name) {
            $indexed[$i] = isset($named[$name]) ? $named[$name] : null;
        }

        return $indexed;
    }

    /**
     * Check if a url was already added to a signal.
     *
     * @param string      $signalName
     * @param UrlContract $url
     *
     * @return bool
     */
    protected function signalHasReceiverUrl($signalName, UrlContract $url)
    {
        foreach ($this->receivers($signalName) as $receiver) {
            if ($receiver->getUrl()->equals($url, ['user', 'host', 'path'])) {
                return true;
            }
        }
        return false;
    }
}