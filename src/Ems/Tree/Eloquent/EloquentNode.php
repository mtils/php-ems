<?php
/**
 *  * Created by mtils on 14.09.18 at 15:05.
 **/

namespace Ems\Tree\Eloquent;


use Ems\Contracts\Tree\Node;
use Ems\Model\Eloquent\Model;
use Ems\Tree\CanHaveChildrenTrait;
use Ems\Tree\CanHaveParentTrait;

class EloquentNode extends Model implements Node
{
    use CanHaveParentTrait;
    use CanHaveChildrenTrait;

    /**
     * @var string
     */
    protected $parentIdKey = 'parent_id';

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

    /**
     * Overwrite this method to update you parent id when the parent
     * changes.
     *
     * @param int|string|null $parentId
     */
    protected function updateParentId($parentId)
    {
        $this->setAttribute($this->parentIdKey, $parentId);
    }

}