<?php

namespace Ems\Contracts\Core;

/**
 * This is a generic provider to provide a minimal implementation to provdide
 * Identifiable objects. A good example is a Template Provider. All you want to do is
 * show a list of Templates and its names in an admin interface. So you have an
 * id and a name.
 **/
interface Provider
{
    /**
     * Get a named object by its id.
     *
     * @param mixed $id
     * @param mixed $default (optional)
     *
     * @return \Ems\Contracts\Core\Identifiable|null
     **/
    public function get($id, $default = null);

    /**
     * Get a named object by its id or throw an exception if it cant be found.
     *
     * @param mixed $id
     *
     * @throws \Ems\Contracts\NotFound
     *
     * @return \Ems\Contracts\Core\Identifiable
     **/
    public function getOrFail($id);
}
