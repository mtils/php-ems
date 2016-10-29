<?php


namespace Ems\Contracts\Model;


use IteratorAggregate;

/**
 * A Result should always be used if you return something
 * from a model.
 * You should not retrieve the results inside your repository, search
 * whatever class which returns results. At this point its better to
 * build a proxy object that will retrieve the results on demand.
 * This is because you often dont know if the user of your model
 * will paginate, return the whole result or discard the request later
 * if something other goes wrong. So delay your expensive operations.
 * This interface is not countable by intent.
 * Iterators allow to process rows one by one which will not explode
 * your memory on large result sets.
 * The result must create a new iterator on every call of getIterator() 
 * (foreach)
 * A DB Query object is a really good candidate for an Result
 * So you could write foreach (User::where('name', 'John') as $user)
 **/
interface Result extends IteratorAggregate
{

    /**
     * Return the creator of this result. This is not a must, but part of this
     * interface
     *
     * @return object
     **/
    public function creator();

}
