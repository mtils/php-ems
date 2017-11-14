<?php


namespace Ems\Contracts\Core;

/**
 * This is a basic interface for all classes which act
 * as a key/value container.
 * It uses a keys() method and not IteratorAggregate to
 * not force loading of all values which could cause
 * expensive operations.
 * The keys() method returns just all available keys.
 * So if the using object has a big set of possible keys
 * which are not necessarily loaded into the object it
 * has to returns this keys too.
 * In an object which does not know which keys it has it
 * has to return just all assigned keys.
 **/
interface HasKeys
{
    /**
     * Return an list keys (should be strings)
     *
     * @return \Ems\Core\Collections\OrderedList
     **/
    public function keys();

}
