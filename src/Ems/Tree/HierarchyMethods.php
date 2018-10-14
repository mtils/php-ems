<?php
/**
 *  * Created by mtils on 27.09.18 at 15:28.
 **/

namespace Ems\Tree;


use function array_reverse;
use Ems\Contracts\Tree\CanHaveParent;
use Ems\Contracts\Tree\Node;
use OverflowException;

trait HierarchyMethods
{
    /**
     * To prevent endless recursion in tree searches set a max depth.
     *
     * @var int
     */
    protected $_maxDepth = 1000;

    /**
     * Return all ancestors of $node. Just look by getParent().
     * The first returned node is the direct parent. The last the topmost.
     *
     * @param CanHaveParent $node
     *
     * @return Node[]
     */
    protected function collectAncestors(CanHaveParent $node)
    {
        $child = $node;
        $i = 0;

        $parents = [];

        while($parent = $child->getParent()) {

            if ($i >= $this->_maxDepth) {
                throw new OverflowException("Giving up to collect parents after $i iterations.");
            }

            $parents[] = $parent;
            $child = $parent;
            $i++;
        }

        return $parents;
    }


    protected function calculatePath(CanHaveParent $node)
    {

        $segments = [$this->cleanSegment($node->getPathSegment())];

        foreach ($this->collectAncestors($node) as $parent) {
            $segments[] = $this->cleanSegment($parent->getPathSegment());
        }

        $topMostNode = isset($parent) ? $parent : $node;

        $start = $topMostNode->isRoot() ? '/' : '';

        return $start . implode('/', array_reverse($segments));
    }

    protected function cleanSegment($segment)
    {
        return trim($segment, '/');
    }
}