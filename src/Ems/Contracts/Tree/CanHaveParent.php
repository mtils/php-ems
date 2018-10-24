<?php
/**
 *  * Created by mtils on 20.09.18 at 07:13.
 **/

namespace Ems\Contracts\Tree;


use Ems\Contracts\Core\Identifiable;

/**
 * Interface CanHaveParent
 *
 * If something can have a (exactly one) parent, it should implement this
 * interface.
 * If you take a filesystem tree a file would be a CanHaveParent but not a
 * Node because it cannot have children.
 *
 * @package Ems\Contracts\Tree
 */
interface CanHaveParent extends Identifiable
{
    /**
     * Returns if node is a root node.
     *
     * @return bool
     */
    public function isRoot();

    /**
     * Returns the parent node of this node.
     *
     * @return Node|null
     */
    public function getParent();

    /**
     * Set the parent node of this node (Only in memory).
     *
     * @param Node $parent
     *
     * @return self
     */
    public function setParent(Node $parent);

    /**
     * Clear the parent, which makes the node a root node.
     *
     * @return self
     **/
    public function clearParent();

    /**
     * Does this node has a parent?
     *
     * @return bool
     */
    public function hasParent();

    /**
     * Return a path segment to identify the node position by a path.
     * This seems to be not needed in a base interface but if you
     * work with a NodeProvider or something like that you want to
     * find the position.
     * The path segment is excluding path separators so for path
     * so /var/www/html it is just "html".
     *
     * The path segment can also be an id. (/17/35/48)
     *
     * @return string
     */
    public function getPathSegment();

    /**
     * Return the complete path to this node. You can generate it by its ancestors
     * (parents) or just return a stored property. By logic it should end with
     * $this->getPathSegment(). Paths are separated by / on every OS in any case.
     *
     * @return string
     */
    public function getPath();

    /**
     * Returns the level of this node.
     *
     * @return int
     */
    public function getLevel();

    /**
     * This is needed to hydrate the trees.
     *
     * @return int|string|null
     **/
    public function getParentId();
}