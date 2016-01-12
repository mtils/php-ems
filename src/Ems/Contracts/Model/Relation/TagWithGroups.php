<?php 

namespace Ems\Contracts\Model\Relation;

use Ems\Contracts\Core\Named;

interface TagWithGroups extends Tag
{
    /**
     * Return all TagGroups assigned to this tag
     *
     * @return \Traversable
     **/
    public function getGroups();

    /**
     * Assign all TagGroups to this Tag
     *
     * @param \Traversable<Ems\Contracts\Model\Relation\TagGroup> $groups
     * @return self
     **/
    public function setGroups($groups);

    /**
     * Add a TagGroup
     *
     * @param \Ems\Contracts\Model\Relation\TagGroup $group
     * @return self
     */
    public function attachGroup(TagGroup $group);

    /**
     * Remove a group from this Tag
     *
     * @param \Ems\Contracts\Model\Relation\TagGroup $group
     * @return self
     */
    public function detachGroup(TagGroup $group);

    /**
     * Check if this tag is in $group
     *
     * @param \Ems\Contracts\Model\Relation\TagGroup $group
     * @return bool
     **/
    public function hasGroup(TagGroup $group);

}