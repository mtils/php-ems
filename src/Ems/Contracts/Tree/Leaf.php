<?php
/**
 *  * Created by mtils on 20.09.18 at 08:20.
 **/

namespace Ems\Contracts\Tree;

/**
 * Interface Leaf
 *
 * A Leaf is a part of a tree that cannot have children by design. This is
 * the case for a file in a filesystem.
 *
 * @package Ems\Contracts\Tree
 */
interface Leaf extends CanHaveParent
{
    //
}