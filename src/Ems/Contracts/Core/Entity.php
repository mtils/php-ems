<?php

namespace Ems\Contracts\Core;

/**
 * An Entity is an object with an id and a resource name
 * Therefore it is routable and mostly cacheable and locatable
 * inside an application.
 **/
interface Entity extends AppliesToResource, Identifiable
{
    /**
     * Return true if this instance is not from data store.
     *
     * @return bool
     **/
    public function isNew();

    /**
     * Return true if something was modified. Pass null to know
     * if the object was modified at all.
     * Pass a string to check if one attribute was modified. Pass
     * an array of attribute nams to now if ANY of them were
     * modified.
     *
     * @param string|array $attributes (optional)
     *
     * @return bool
     **/
    public function wasModified($attributes=null);
}
