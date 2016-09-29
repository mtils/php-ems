<?php


namespace Ems\Contracts\Core;

use Countable;

/**
 * A named quantity is usefull for all group by results
 * If you have a blog and list its categories, tags or months
 * with a count of the entries in int this would be the object you
 * use to display them.
 * (id =tag id, name=tag name, coun(NamedQuantity) -> amount of entries
 *
 **/
interface NamedQuantity extends Named, Countable {}
