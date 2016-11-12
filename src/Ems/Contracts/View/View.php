<?php

namespace Ems\Contracts\View;

use Ems\Contracts\Core\Renderable;
use ArrayAccess;
use IteratorAggregate;
use Countable;

interface View extends Renderable, ArrayAccess, IteratorAggregate, Countable
{
    /**
     * Return the name of the view (its path or a string translatable
     * into a path.
     *
     * @return string
     **/
    public function name();

    /**
     * Fill the view variables.
     *
     * @param array|\Traversable|string $name
     * @param mixed                     $value (optional)
     *
     * @return self
     **/
    public function assign($name, $value = null);

    /**
     * Return all assigned variables as an array.
     *
     * @return array
     **/
    public function assignments();
}
