<?php

namespace Ems\Contracts\Expression;

use Ems\Contracts\Core\Expression as ExpressionContract;
use Ems\Contracts\Model\Result;
use IteratorAggregate;

/**
 * This is a interface for all queries, results,... which can be prepared.
 **/
interface Preparable
{
    /**
     * Make a prepared statement out of something
     *
     * @return Prepared
     **/
    public function prepare();

}
