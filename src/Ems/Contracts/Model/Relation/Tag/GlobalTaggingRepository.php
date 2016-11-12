<?php

namespace Ems\Contracts\Model\Relation\Tag;

/**
 * The GlobalTaggingRepository is a version of the TaggingRepository
 * which stores tags globally.
 * It typically replicated itself to act as a normal TaggingRepository
 * if you call by($resourceName)
 * If you call all() without by() it should return all tags for
 * all resources
 * attachTags() and syncTags() should only work with "AppliesToResource" objects.
 **/
interface GlobalTaggingRepository extends TaggingRepository
{
    /**
     * This is for method chaining
     * $repo->by('users')->all() returns all tags for 'users'.
     *
     * @param string|\Ems\Contracts\Core\AppliesToResource $resource
     *
     * @return self
     **/
    public function by($resource);

    /**
     * Return a list of all unique resourceNames of stored tags.
     *
     * @return array
     **/
    public function resourceNames();
}
