<?php


namespace Ems\Contracts\Core;

/**
 * This is a generic provider to provide a minimal implementation to provdide
 * Identifiable objects. A good example is a Template Provider. All you want to do is
 * show a list of Templates and its names in an admin interface. So you have an
 * id and a name.
 **/
interface ResourceProvider
{

    /**
     * Get a named object by its id
     *
     * @param mixed $id
     * @return \Ems\Contracts\Core\Identifiable|null
     **/
    public function get($id);

    /**
     * Get a named object by its id or throw an exception if it cant be found
     *
     * @param mixed $id
     * @return \Ems\Contracts\Core\Identifiable
     * @throws \Ems\Contracts\NotFound
     **/
    public function getOrFail($id);

    /**
     * Return an iterable of all known named objects of this provider
     *
     * @return array|\Traversable<\Ems\Contracts\Core\Identifiable>
     **/
    public function all();

}