<?php


namespace Ems\Contracts\Cache;

/**
 * The Categorizer provides the key for objects, tags and lifetime for the
 * stored data. Return null if you have no idea about the object so that
 * another categorizer can try it
 *
 **/
interface Categorizer
{

    /**
     * Return the cache key of $value
     *
     * @param mixed $value
     * @return string|null $id
     **/
    public function key($value);

    /**
     * Return the tags of $value
     *
     * @param mixed $value
     * @return array|null
     **/
    public function tags($value);

    /**
     * Return how long the cache item should be saved
     *
     * @param mixed $value
     * @return \DateTime|null
     **/
    public function lifetime($value);

}
