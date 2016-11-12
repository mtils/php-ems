<?php

namespace Ems\Contracts\Tree;

use Traversable;
use Countable;
use ArrayAccess;

interface Children extends Traversable, Countable, ArrayAccess
{
    /**
     * Append a node to the list.
     *
     * @param \Ems\Contracts\Tree\Node $node
     *
     * @return self
     **/
    public function append(Node $node);

    /**
     * Remove a node from the list.
     *
     * @param \Ems\Contracts\Tree\Node $node
     *
     * @return self
     **/
    public function remove(Node $node);

    /**
     * Removes all nodes.
     *
     * @return self
     **/
    public function clear();
}
