<?php

namespace Ems\Contracts\View;

use Ems\Contracts\Core\AppliesToResource;

/**
 * The HighlightItemProvider provides the actual models from database.
 * This is the class you usually have to implement.
 **/
interface HighlightItemProvider extends AppliesToResource
{
    /**
     * Return the latest $limit $items with the passed $attributes.
     *
     * @param array $attributes (optional)
     * @param int   $limit      (optional)
     *
     * @return \Traversable|array
     **/
    public function latest(array $attributes = [], $limit = null);

    /**
     * Return the top $limit $items with the passed $attributes.
     *
     * @param array $attributes (optional)
     * @param int   $limit      (optional)
     *
     * @return \Traversable|array
     **/
    public function top(array $attributes = [], $limit = null);

    /**
     * Return some $limit $items with the passed $attributes.
     *
     * @param array $attributes (optional)
     * @param int   $limit      (optional)
     *
     * @return \Traversable|array
     **/
    public function some(array $attributes = [], $limit = null);

    /**
     * Return a count of the result for $method
     * method can be latest|top|some.
     *
     * @param string $method
     * @param array  $attributes (optional)
     *
     * @return int
     **/
    public function count($method, array $attributes = []);
}
