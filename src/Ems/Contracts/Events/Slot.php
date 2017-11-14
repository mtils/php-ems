<?php
/**
 * Created by PhpStorm.
 * User: michi
 * Date: 10.11.17
 * Time: 12:42
 */

namespace Ems\Contracts\Events;


/**
 * Class Slot
 *
 * A slot is a description of any type of event or hook you are accepting
 * on an opened (to outside) listener.
 * It should somehow predicable by its description or name and its
 * parameters what it does.
 * You are not creating slots internally. Slot are created to describe what
 * you accept.
 *
 * @package Ems\Contracts\Events
 */
class Slot extends Message
{
    //
}