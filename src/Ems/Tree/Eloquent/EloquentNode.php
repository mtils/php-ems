<?php
/**
 *  * Created by mtils on 14.09.18 at 15:05.
 **/

namespace Ems\Tree\Eloquent;


use Ems\Contracts\Tree\Children;
use Ems\Contracts\Tree\Node;
use Ems\Tree\GenericChildren;
use Ems\Model\Eloquent\Model;

class EloquentNode extends Model implements Node
{
    /**
     * @var string
     */
    protected $parentIdKey = 'parent_id';

    /**
     * @var static|null
     */
    protected $parent;

    /**
     * @var Children
     */
    protected $children;

    /**
     * @inheritDoc
     */
    public function isRoot()
    {
        return !(bool)$this->getParentId();
    }

    /**
     * @inheritDoc
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @inheritDoc
     */
    public function setParent(Node $parent)
    {
        $this->parent = $parent;
        if ($id = $parent->getId()) {
            $this->setAttribute($this->parentIdKey, $id);
        }
        $parent->getChildren()->append($this);
        //$parent->addChild($this);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function clearParent()
    {
        $this->parent = null;
        $this->setAttribute($this->parentIdKey, null);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getChildren()
    {
        if (!$this->children) {
            $this->clearChildren();
        }
        return $this->children;
    }

    /**
     * @inheritDoc
     */
    public function clearChildren()
    {
        if (!$this->children) {
            $this->children = new GenericChildren([]);
        }
        $this->children->clear();
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function hasChildren()
    {
        return (bool)count($this->getChildren());
    }

    /**
     * @inheritDoc
     */
    public function addChild(Node $child)
    {
        $this->getChildren()->append($child);
        $child->setParent($this);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function removeChild(Node $child)
    {
        $this->getChildren()->remove($child);
        $child->clearParent();
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function hasParent()
    {
        return (bool)$this->getParentId();
    }

    /**
     * @inheritDoc
     */
    public function getLevel()
    {
        return $this->getAttribute('level');
    }

    /**
     * @inheritDoc
     */
    public function getParentId()
    {
        return $this->getAttribute($this->parentIdKey);
    }

}