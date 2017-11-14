<?php


namespace Ems\Core;

use Ems\Contracts\Core\ArrayWithState as ArrayWithStateContract;
use Ems\Core\Support\TrackedArrayDataTrait;

class ArrayWithState implements ArrayWithStateContract
{
    use TrackedArrayDataTrait;
}
