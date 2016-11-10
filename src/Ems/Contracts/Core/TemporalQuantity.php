<?php


namespace Ems\Contracts\Core;

use Countable;

/**
 * A temporal quantity is usefull for all group by results
 * If you have a blog and list its years, months or days
 * with a count of the entries in int this would be the object you
 * use to display them.
 * (id =timestamp, name=January name, coun(NamedQuantity) -> amount of entries
 *
 **/
interface TemporalQuantity extends PointInTime, Countable {}
