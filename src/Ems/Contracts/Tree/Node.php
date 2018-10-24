<?php

namespace Ems\Contracts\Tree;

/**
 * Interface Node
 *
 * The Node is a fully functional tree node. It can have children and a
 * parent.
 * The Tree interfaces and its methods are only for in memory processing.
 * In every case you have to do a separate persist to store any structure.
 *
 * @package Ems\Contracts\Tree
 */
interface Node extends CanHaveParent, CanHaveChildren
{
    //
}
