<?php


namespace Ems\Model\Database;

use Ems\Contracts\Expression\Prepared;
use Ems\Contracts\Core\Stringable;
use Ems\Core\Support\StringableTrait;
use Ems\Model\ResultTrait;
use Exception;
use PDO;
use PDOStatement;


class PDOPrepared implements Prepared
{
    use ResultTrait;
    use StringableTrait;

    /**
     * @var string
     **/
    protected $query;

    /**
     * @var array
     **/
    protected $bindings = [];

    /**
     * @var PDOStatement
     **/
    protected $statement;

    /**
     * @var callable
     **/
    protected $statementCreator;

    /**
     * @var bool
     **/
    protected $returnAffected = null;

    /**
     * @var callable
     **/
    protected $errorHandler;

    /**
     * Pass in a prepared statement without bindings. The bindings
     * will be applied by this object.
     *
     * @param PDOStatement                          $statement
     * @param string|\Ems\Contracts\Core\Stringable $query
     * @param bool                                  $returnAffected (default:false)
     * @param callable                              $errorHandler (optional)
     **/
    public function __construct(PDOStatement $statement, $query, $returnAffected=true, callable $errorHandler=null)
    {
        $this->_creator = $query instanceof Stringable ? $query : null;
        $this->statement = $statement;
        $this->returnAffected = $returnAffected;
        $this->query = $query;
        $this->errorHandler = $errorHandler;
    }

    /**
     * {@inheritdoc}
     *
     * @return string|\Ems\Contracts\Core\Stringable
     **/
    public function query()
    {
        return $this->query;
    }

    /**
     * {@inheritdoc}
     *
     * @param array $bindings
     *
     * @return self
     **/
    public function bind(array $bindings)
    {
        $this->bindings = $bindings;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param array $bindings (optional)
     * @param bool  $returnAffectedRows (optional)
     *
     * @return int|null (null if no affected rows should be returned)
     **/
    public function write(array $bindings=null, $returnAffectedRows=null)
    {

        $returnAffectedRows = $returnAffectedRows !== null ? $returnAffectedRows : $this->returnAffected;

        $bindings = is_array($bindings) ? $bindings : $this->bindings;

        $this->bindAndRun($bindings);

        if (!$returnAffectedRows) {
            return;
        }

        return $this->statement->rowCount();
    }

    /**
     * Return an iterator to traverse over the result.
     *
     * @return \Iterator
     **/
    public function getIterator()
    {

        $this->bindAndRun($this->bindings);

        while ($row = $this->statement->fetch()) {
            yield $row;
        }
    }

    protected function bindAndRun(array $bindings)
    {
        try {
            static::bindToStatement($this->statement, $bindings);
            return $this->statement->execute();
        } catch (Exception $e) {
            //
        }

        if ($this->errorHandler) {
            call_user_func($this->errorHandler, $e, $this->query);
        }
    }

    /**
     * Add bindings to a statement. Cast integers, null, bool and string.
     *
     * @param PDOStatement $statement
     * @param array $bindings
     **/
    public static function bindToStatement(PDOStatement $statement, array $bindings=[])
    {
        foreach ($bindings as $key=>$value) {

            $casted = is_bool($value) ? (int)$value : $value;

            $statement->bindValue(
                is_string($key) ? $key : $key + 1,
                $casted,
                is_int($casted) ? PDO::PARAM_INT : ($casted === null ? PDO::PARAM_NULL : PDO::PARAM_STR)
            );

        }
    }

    public function toString()
    {
        return SQL::sql($this->query, $this->bindings);
    }
}
