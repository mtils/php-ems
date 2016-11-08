<?php


namespace Ems\Contracts\Core;

/**
 * The "All"-Provider can provide all objects.
 **/
interface AllProvider extends Provider
{
    /**
     * Return an iterable of all known named objects of this provider
     *
     * @return array|\Traversable<\Ems\Contracts\Core\Identifiable>
     **/
    public function all();

}
