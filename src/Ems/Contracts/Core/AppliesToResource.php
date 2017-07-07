<?php

namespace Ems\Contracts\Core;

/**
 * A resource is anything that's important enough to be referenced as a thing
 * itself (in your application).
 * EMS takes the mostly used naming convention in REST applications: plural
 * minus lowercase names.
 * So if you have an entity of class App\User its resourceName would be users.
 * An entity named App\Blog\BlogEntry would be named blog-entries.
 **/
interface AppliesToResource
{
    /**
     * Return the resource name. See class doc.
     *
     * @return string
     **/
    public function resourceName();
}
