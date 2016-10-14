<?php


namespace Ems\Contracts\View;

use Ems\Contracts\Cache\Cacheable;
use IteratorAggregate;

/**
 * The getIterator method of IteratorAggregate has to return the plain
 * model items.
 * The count method is used to count all objects matching the passed critera
 **/
interface Highlight extends View, Cacheable
{

    /**
     * Randomize the returned result. Store an amount of $combinations to not
     * explode the cache system. $combinations=0 means not randomize at all.
     * no call to randomize does also means dont randomize.
     *
     * @param int $combinations (optional)
     * @return self
     **/
    public function randomize($combinations=5);

    /**
     * Set a limit of the returned items
     *
     * @param int $limit
     * @return self
     **/
    public function limit($limit);

    /**
     * Add parameters to the query. The name of the called method will
     * be added to the query parameters while passing to HighlightItemProvider
     *
     * @example News::latest(5)->of($moderator); // results in HighlightItemProvider::latest(['of'=>$moderator], 4)
     *
     * @param mixed $criteria
     * @param array $parameters (optional)
     * @return self
     **/
    public function __call($criteria, $parameters=[]);

    /**
     * Set the template $template. If you want to manipulate the used
     * template use this method. The highlight just returns itself so
     * use the result of this method as a string
     *
     * @param string $template (optional)
     * @return self
     **/
    public function render($template='');

    /**
     * Set the item provider to allow deferred loading
     *
     * @param \Ems\Contracts\View\HighlightItemProvider $provider
     * @return self
     **/
    public function setItemProvider(HighlightItemProvider $provider);

    /**
     * Set the method which will be called on the HighlightItemProvider
     *
     * @param string (latest|top|random)
     * @return self
     **/
    public function method($method);

    /**
     * Return the assigmed criterias
     *
     * @return array
     **/
    public function criterias();

}
