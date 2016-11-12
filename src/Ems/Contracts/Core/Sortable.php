<?php

namespace Ems\Contracts\Core;

/**
 * The sortable interface is for sorting objects in memory.
 **/
interface Sortable
{
    /**
     * Get the position of this sortable (inside its parent).
     *
     * @return int
     **/
    public function getPosition();

    /**
     * Returns the position of this sortable (inside its parent).
     *
     * @param int $position
     *
     * @return self
     **/
    public function setPosition($position);

    /**
     * Get the previos sortable (like DOM.previousSibling).
     *
     * @return \Ems\Contracts\Core|null
     **/
    public function getPrevious();

    /**
     * Set the previous Sortable. Reset the sortable via
     * setPosition(0).
     *
     * @param self $previous
     *
     * @return self
     **/
    public function setPrevious(self $previous);

    /**
     * Get the next sibling.
     *
     * @return \Ems\Contracts\Core|null|null
     **/
    public function getNext();

    /**
     * Set the next sibling.
     *
     * @param \Ems\Contracts\Core|null $next
     *
     * @return self
     **/
    public function setNext(self $next);
}
