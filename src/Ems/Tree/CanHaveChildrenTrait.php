<?php
/**
 *  * Created by mtils on 20.09.18 at 09:41.
 **/

namespace Ems\Tree;

use Ems\Contracts\Tree\Children;

trait CanHaveChildrenTrait
{
    /**
     * @var Children
     */
    protected $_children;

    /**
     * @inheritDoc
     */
    public function getChildren()
    {
        if (!$this->_children) {
            $this->_children = new GenericChildren([], $this);
        }
        return $this->_children;
    }

    /**
     * @inheritDoc
     */
    public function hasChildren()
    {
        return (bool)count($this->getChildren());
    }
}