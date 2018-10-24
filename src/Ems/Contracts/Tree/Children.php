<?php

namespace Ems\Contracts\Tree;

use Countable;
use Ems\Contracts\Model\OrmCollection;

/**
 * Interface Children
 *
 * This interface is for all node collections under one node.
 *
 * @package Ems\Contracts\Tree
 */
interface Children extends OrmCollection, Countable
{
    /**
     * Append a node to the list.
     *
     * @param CanHaveParent $node
     *
     * @return self
     **/
    public function append(CanHaveParent $node);

    /**
     * Remove a node from the list.
     *
     * @param CanHaveParent $node
     *
     * @return self
     **/
    public function remove(CanHaveParent $node);

    /**
     * Removes all nodes.
     *
     * @return self
     **/
    public function clear();
}
