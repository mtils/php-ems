<?php

namespace Ems\Model\Database;

use Ems\Contracts\Model\Result;
use Ems\Model\ResultTrait;
use PDOStatement;

class PDOResult implements Result
{
    use ResultTrait;

    /**
     * @var PDOStatement
     **/
    protected $statement;

    /**
     * Pass a callable which will return the complete result.
     *
     * @param callable $getter
     * @param object   $creator (optional)
     **/
    public function __construct(PDOStatement $statement, $creator = null)
    {
        $this->statement = $statement;
        $this->_creator = $creator;
    }

    /**
     * Return an iterator to traverse over the result.
     *
     * @return \Iterator
     **/
    public function getIterator()
    {
        $this->statement->execute();
        while ($row = $this->statement->fetch()) {
            yield $row;
        }
    }

}
