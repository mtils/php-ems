<?php
/**
 *  * Created by mtils on 14.09.18 at 11:14.
 **/

namespace Ems\Contracts\Tree;


use Ems\Contracts\Core\Repository;

/**
 * Interface NodeRepository
 *
 * A NodeRepository is the Core\Repository counterpart that allows to
 * save nodes.
 *
 * @package Ems\Contracts\Tree
 */
interface NodeRepository extends Repository, NodeProvider
{
    /**
     * Make the next save(), make() or create as a child of $node.
     *
     * @param Node $node
     *
     * @return self
     */
    public function asChildOf(Node $node);

    /**
     * Instantiate a new model and fill it with the attributes.
     *
     * @param array $attributes
     *
     * @return Node The instantiated resource
     **/
    public function make(array $attributes = []);

    /**
     * Create a new model by the given attributes and persist
     * it.
     *
     * @param array $attributes
     *
     * @return Node The created resource
     **/
    public function store(array $attributes);

}