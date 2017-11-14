<?php

namespace Ems\Contracts\Expression;

use Ems\Contracts\Core\Expression as ExpressionContract;
use Ems\Contracts\Model\Result;
use IteratorAggregate;

/**
 * This is the abstract version of a prepared statement.
 * It is not only used for real statements, but to not parse queries
 * on every call if the queries just look the same.
 * Just iterate of the object itself with foreach to fetch the results.
 **/
interface Prepared extends Result
{
    /**
     * Return the original query (string)
     *
     * @return string|\Ems\Contracts\Core\Stringable
     **/
    public function query();

    /**
     * Change all bindings of the statement. (Before fetching again)
     *
     * @param array $bindings
     *
     * @return self
     **/
    public function bind(array $bindings);

    /**
     * Perform a altering query with the passed binding. Return the
     * the number of affected rows id $returnAffectedRows is true.
     *
     * @param array $bindings (optional)
     * @param bool  $returnAffectedRows (optional)
     *
     * @return int|null (null if no affected rows should be returned)
     **/
    public function write(array $bindings=null, $returnAffectedRows=null);
}
