<?php
/**
 *  * Created by mtils on 02.02.18 at 05:42.
 **/

namespace Ems\Contracts\Model;

interface SupportsTransactions
{
    /**
     * Run the callable in a transaction.
     * begin(); $run(); commit();
     *
     * @param callable $run
     * @param int      $attempts (default:1)
     *
     * @return mixed The result of the callable
     **/
    public function transaction(callable $run, $attempts=1);

    /**
     * Return if a transaction is currently running.
     *
     * @return bool
     **/
    public function isInTransaction();
}