<?php
/**
 *  * Created by mtils on 14.09.18 at 10:30.
 **/

namespace Ems\Contracts\Tree;


use Ems\Contracts\Core\Provider;
use Ems\Contracts\Core\Errors\NotFound;

/**
 * Interface NodeProvider
 *
 * A NodeProvider is the counterpart of a provider just by adding
 * some methods to it to work with trees.
 *
 * The methods are just overwritten to correct the '@return' type hints
 *
 * @package Ems\Contracts\Tree
 */
interface NodeProvider extends Provider
{
    /**
     * Get a node by its id.
     *
     * @param mixed $id
     * @param Node  $default (optional)
     *
     * @return Node
     */
    public function get($id, $default = null);

    /**
     * Get an object by its id or throw an exception if it cant be found.
     *
     * @param mixed $id
     *
     * @throws \Ems\Contracts\Core\Errors\NotFound
     *
     * @return Node
     **/
    public function getOrFail($id);

    /**
     * Perform the next get(), getOrFail() or getByPath()
     * with $depth levels of recursion
     *
     * @param int $depth default:1
     *
     * @return self
     */
    public function recursive($depth=1);

    /**
     * Return a node by path or $default if not found. Supports also depth.
     *
     * @param string        $path
     * @param CanHaveParent $default [optional]
     *
     * @return CanHaveParent
     */
    public function getByPath($path, CanHaveParent $default=null);

    /**
     * Return a node by path or throw an exception.
     *
     * Supports also depth via self::recursive().
     *
     * @param string $path
     *
     * @return CanHaveParent
     *
     * @throws NotFound
     */
    public function getByPathOrFail($path);

    /**
     * Find nodes by its path segment (CanHaveParent::getPathSegment()).
     * In a filesystem like structure it would be basename.
     *
     * @param string $segment
     *
     * @return CanHaveParent[]
     */
    public function findBySegment($segment);

    /**
     * Return the children of $parent.
     *
     * @param Node $parent
     *
     * @return Node[]
     */
    public function children(Node $parent);

    /**
     * Return the parent node of $node if it exists.
     *
     * @param CanHaveParent $child
     *
     * @return Node|null
     */
    public function parent(CanHaveParent $child);

    /**
     * Return all ancestors of $child. ($child->getParent()->getParent()->getParent())
     * ALSO assign the parent to $child and each parent up the tree.
     *
     * @param CanHaveParent $child
     *
     * @return Node[]
     */
    public function ancestors(CanHaveParent $child);
}