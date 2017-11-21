<?php

namespace Ems\Contracts\Model\Relation\Tag;

interface TaggingRepository
{
    /**
     * Return all available tags.
     *
     * @return \Traversable
     **/
    public function all();

    /**
     * Attach the tags to the passed holder(s).
     *
     * @param HoldsTags|\Traversable $holders
     *
     * @return self
     **/
    public function attachTags(&$holders);

    /**
     * Persist the attached tags (to database).
     *
     * @param HoldsTags $holder
     *
     * @return self
     **/
    public function syncTags(HoldsTags $holder);

    /**
     * Create a new tag named $name without persisting it.
     *
     * @param string $name
     *
     * @return Tag
     **/
    public function make($name);

    /**
     * Create a new tag in storage and return it.
     *
     * @param string $name
     *
     * @return Tag
     **/
    public function create($name);

    /**
     * Get the tag with name $name or create it.
     *
     * @param string $name
     *
     * @return Tag
     **/
    public function getByNameOrCreate($name);

    /**
     * Get the tag with id $id.
     *
     * @param int $id
     *
     * @return \Ems\Contracts\Model\Relation\Tag
     **/
    public function getOrFail($id);

    /**
     * Delete a tag.
     *
     * @param Tag
     *
     * @return self
     **/
    public function delete(Tag $tag);
}
