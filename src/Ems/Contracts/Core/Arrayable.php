<?php


namespace Ems\Contracts\Core;

/**
 * Turn an object (fast) into an array. Only root should
 * be an array, dont test for a toArray() method of childs
 * while building the array.
 **/
interface Arrayable
{
    /**
     * This is a performance related method. In this method
     * you should implement the fastest was to get every
     * key and value as an array.
     * Only the root has to be an array, it should not build
     * the array by recursion.
     *
     * @return array
     **/
    public function toArray();
}
