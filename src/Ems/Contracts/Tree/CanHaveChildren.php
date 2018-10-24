<?php
/**
 *  * Created by mtils on 20.09.18 at 06:57.
 **/

namespace Ems\Contracts\Tree;


/**
 * Interface HasChildren
 *
 * @package Ems\Contracts\Tree
 */
interface CanHaveChildren
{
    /**
     * Returns the children of this node.
     *
     * All node handling is done on the Children object.
     *
     * @return Children
     */
    public function getChildren();

    /**
     * Does this node have children? This is a performance related method and
     * avoids asking (automatic populated) children if you know before that
     * there are none.
     *
     * @return bool
     */
    public function hasChildren();

}