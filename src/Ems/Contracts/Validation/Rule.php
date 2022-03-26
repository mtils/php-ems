<?php


namespace Ems\Contracts\Validation;

use Countable;
use IteratorAggregate;
use Ems\Contracts\Expression\ConstraintGroup;

/**
 * A Rule represents a constraint description for one
 * value. So instead of just parsing strings this makes manipulations
 * much easier. (e.g. $definition->min = 3 or $definition->between = [2,8])
 *
 **/
interface Rule extends ConstraintGroup, Countable, IteratorAggregate
{
    //
}
