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
     * @param string $path
     * @param Node   $default [optional]
     *
     * @return Node
     */
    public function getByPath($path, Node $default=null);

    /**
     * Return a node by path or throw an exception.
     *
     * Supports also depth via self::recursive().
     *
     * @param string $path
     *
     * @return Node
     *
     * @throws NotFound
     */
    public function getByPathOrFail($path);

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
     * @param Node $child
     *
     * @return Node|null
     */
    public function parent(Node $child);
}