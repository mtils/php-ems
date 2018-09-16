<?php

namespace Ems\Contracts\Tree;

use Ems\Contracts\Core\Identifiable;

interface Node extends Identifiable
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
     * Returns the children of this node.
     * 
     * @return Children
     */
    public function getChildren();

    /**
     * Clears all childNodes. (Only in memory).
     * 
     * @return self
     */
    public function clearChildren();

    /**
     * Adds a childNode to this node (Only in memory).
     *
     * @param Node $child
     *
     * @return self
     */
    public function addChild(Node $child);

    /**
     * Removes a child node (Only in memory).
     *
     * @param Node $child
     *
     * @return self
     */
    public function removeChild(Node $child);

    /**
     * Does this node have children?
     * 
     * @return bool
     */
    public function hasChildren();

    /**
     * Does this node has a parent?
     * 
     * @return bool
     */
    public function hasParent();

    /**
     * Returns the level of this node.
     * 
     * @return int
     */
    public function getLevel();

    /**
     * This is needed to hydrate the trees.
     *
     * @return int|string
     **/
    public function getParentId();
}
