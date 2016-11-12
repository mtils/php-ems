<?php

namespace Ems\Contracts\Model\Relation\Tag;

/**
 * This is an interface for any taggable object. This interface
 * is meant to be completely in-memory.
 * So if you populate your models from db, the models will be
 * filled with the tags. If you save your model you have to assign
 * the tags first and the repository storing the data will read
 * the assigned tags from the object.
 **/
interface HoldsTags
{
    /**
     * Return all Tags assigned to this object.
     *
     * @return \Traversable
     **/
    public function getTags();

    /**
     * Assign all Tags to this Tag.
     *
     * @param \Traversable<Ems\Contracts\Model\Relation\Tag> $tags
     *
     * @return self
     **/
    public function setTags($tags);

    /**
     * Add a Tag.
     *
     * @param \Ems\Contracts\Model\Relation\Tag $tag
     *
     * @return self
     */
    public function attachTag(Tag $tag);

    /**
     * Remove a tag from this object.
     *
     * @param \Ems\Contracts\Model\Relation\Tag $tag
     *
     * @return self
     */
    public function detachTag(Tag $tag);

    /**
     * Check if this object has a tag $tag.
     *
     * @param \Ems\Contracts\Model\Relation\Tag $tag
     *
     * @return bool
     **/
    public function hasTag(Tag $tag);
}
