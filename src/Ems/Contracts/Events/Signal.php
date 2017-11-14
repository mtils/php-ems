<?php
/**
 * Created by PhpStorm.
 * User: michi
 * Date: 10.11.17
 * Time: 12:42
 */

namespace Ems\Contracts\Events;


/**
 * Class Signal
 *
 * A signal is a description of any type of event or hook you are exposing
 * to outside.
 * It should somehow predicable by its description or name and its
 * parameters what it does.
 * You are not firing signals. Signals describe what you are firing.
 *
 * @package Ems\Contracts\Events
 */
class Signal extends Message
{
    //
}