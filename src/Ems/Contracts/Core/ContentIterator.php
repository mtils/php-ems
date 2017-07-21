<?php


namespace Ems\Contracts\Core;

use Iterator;
use Countable;


/**
 * A ContentIterator allows to read file contents like a stream. It also can
 * count the bytes (or lines or rows)
 **/
interface ContentIterator extends Iterator, Countable
{
}
