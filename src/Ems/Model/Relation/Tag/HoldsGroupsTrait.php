<?php


namespace Ems\Model\Relation\Tag;

use OutOfBoundsException;
use Ems\Contracts\Model\Relation\Tag\TagGroup;

/**
 * @see \Ems\Contracts\Model\Relation\Tag\TagWithGroups
 **/
trait HoldsGroupsTrait
{

    /**
     * @var array
     **/
    protected $_groups = [];

    /**
     * {@inheritdoc}
     *
     * @return \Traversable
     **/
    public function getGroups()
    {
        return $this->_groups;
    }

    /**
     * Assign all TagGroups to this Tag
     *
     * @param \Traversable<Ems\Contracts\Model\Relation\TagGroup> $groups
     * @return self
     **/
    public function setGroups($groups)
    {
        $this->groups = (array)$groups;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param \Ems\Contracts\Model\Relation\TagGroup $group
     * @return self
     */
    public function attachGroup(TagGroup $group)
    {
        if (!$this->hasGroup($group)) {
            $this->_groups[] = $group;
        }
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param \Ems\Contracts\Model\Relation\TagGroup $group
     * @return self
     */
    public function detachGroup(TagGroup $group)
    {

        if (!$this->hasGroup($tag)) {
            throw new OutOfBoundsException('Group ' . $group->getName() . ' is not attached');
        }

        $this->_groups = array_filter($this->_groups, function($knownGroup) use ($group) {
            return ($group->getId() != $knownGroup->getId());
        });

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param \Ems\Contracts\Model\Relation\TagGroup $group
     * @return bool
     **/
    public function hasGroup(TagGroup $group)
    {
        foreach ($this->_groups as $knownGroup) {
            if ($knownGroup->getId() == $group->getId()) {
                return true;
            }
        }

        return false;
    }
}