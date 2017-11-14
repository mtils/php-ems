<?php
/**
 * Created by PhpStorm.
 * User: michi
 * Date: 10.11.17
 * Time: 12:42
 */

namespace Ems\Contracts\Events;

use Ems\Core\Helper;

/**
 * Class Message
 *
 * A message is a description of any listener or event you are exposing.
 * You are not firing messages. Signals describe what you are firing or
 * accepts on a listener endpoint.
 *
 * @package Ems\Contracts\Events
 */
class Message
{

    /**
     * A name for that message. Mostly the eventName will be used.
     *
     * @var string
     */
    public $name = '';

    /**
     * The internal used event name.
     *
     * @var string
     */
    public $eventName = '';

    /**
     * Just the name of the parameters.
     *
     * @var array
     */
    public $parameters = [];

    /**
     * Optionally assign an xtype for the parameters.
     *
     * @var \Ems\Contracts\XType\XType
     */
    public $parameterType;

    /**
     * Assign a description for better reading in apis.
     *
     * @var string
     */
    public $description = '';

    /**
     * @var \Ems\Contracts\Core\Url
     */
    public $url;

    /**
     * An artificial signature, which allows fast reading.
     *
     * @var string
     */
    public $signature = '';

    /**
     * Message constructor.
     *
     * @param array $values
     */
    public function __construct(array $values=[])
    {
        $this->fill($values);
    }

    /**
     * @param array $values
     */
    public function fill(array $values)
    {
        foreach(get_class_vars(get_class($this)) as $key=>$value) {
            if (array_key_exists($key, $values)) {
                $this->$key = $values[$key];
            }
        }

        if (!$this->name && $this->eventName) {
            $this->name = $this->eventName;
        }
    }

    public function __toString()
    {

        if ($this->signature) {
            return $this->signature;
        }

        $parameters = [];

        foreach ($this->parameters as $key=>$value) {

            if (is_numeric($key)) {
                $parameters[] = "mixed \$$value";
                continue;
            }

            $parameters[] = Helper::typeName($value) . " $$key";
        }

        return $this->name . '(' . implode(', ', $parameters) . ')';
    }
}