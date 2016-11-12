<?php

namespace Ems\Contracts\Assets;

use Countable;
use ArrayAccess;
use IteratorAggregate;

interface Collection extends Renderable, Countable, ArrayAccess, IteratorAggregate
{
}
