<?php
/**
 *  * Created by mtils on 20.09.18 at 09:42.
 **/

namespace Ems\Tree;


use Ems\Contracts\Tree\Node;
use OverflowException;

/**
 * Trait CanHaveParentTrait
 *
 * @see \Ems\Contracts\Tree\CanHaveParent
 *
 * @package Ems\Tree
 */
trait CanHaveParentTrait
{
    use HierarchyMethods;

    /**
     * @var Node|null
     */
    protected $_parent;

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function isRoot()
    {
        return !$this->hasParent();
    }

    /**
     * {@inheritdoc}
     *
     * @return Node|null
     */
    public function getParent()
    {
        return $this->_parent;
    }

    /**
     * {@inheritdoc}
     *
     * @param Node $parent
     *
     * @return self
     */
    public function setParent(Node $parent)
    {
        $this->_parent = $parent;
        if ($id = $parent->getId()) {
            $this->updateParentId($id);
        }
        $parent->getChildren()->append($this);
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return self
     **/
    public function clearParent()
    {
        $this->_parent = null;
        $this->updateParentId(null);
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function hasParent()
    {
        return (bool)$this->getParentId();
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getPathSegment()
    {
        return (string)$this->getId();
    }


    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getPath()
    {
        return $this->calculatePath($this);
    }

    /**
     * {@inheritdoc}
     *
     * @return int|string|null
     **/
    public function getParentId()
    {
        return $this->_parent ? $this->_parent->getId() : null;
    }

    /**
     * Overwrite this method to update you parent id when the parent
     * changes.
     *
     * @param int|string|null $parentId
     */
    protected function updateParentId($parentId)
    {
        //
    }
}