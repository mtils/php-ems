<?php

namespace Ems\Model\Database\Dialects;

use DateTime;
use Ems\Contracts\Core\Expression as ExpressionContract;
use Ems\Contracts\Model\Database\Dialect;
use Ems\Contracts\Model\Database\NativeError;
use Ems\Contracts\Model\Database\SQLException;
use Ems\Contracts\Expression\Condition;
use Ems\Contracts\Expression\ConditionGroup;
use Ems\Contracts\Expression\LogicalGroup;
use Ems\Contracts\Expression\Constraint;
use Ems\Contracts\Expression\ConstraintGroup;
use Ems\Core\Support\StringableTrait;
use Ems\Core\Exceptions\UnsupportedParameterException;
use Ems\Core\Expression;
use Ems\Core\Helper;
use Ems\Core\KeyExpression;
use Ems\Model\Database\SQLConstraintException;
use Ems\Model\Database\SQLDeniedException;
use Ems\Model\Database\SQLIOException;
use Ems\Model\Database\SQLExceededException;
use Ems\Model\Database\SQLLockException;
use Ems\Model\Database\SQLNameNotFoundException;
use Ems\Model\Database\SQLSyntaxException;


use Exception;
use InvalidArgumentException;

class SQLiteDialect implements Dialect
{
    use StringableTrait;

    protected $operatorMap = [
        '='     => '=',
        '!='    => '<>',
        '<>'    => '<>',
        '>'     => '>',
        '>='    => '>=',
        '<'     => '<',
        '<='    => '<=',
        'not'   => 'IS NOT',
        'is'    => 'IS',
        'like'  => 'LIKE',
        'in'    => 'IN'
    ];

    /**
     * SQLite error codes (from source)
     **/
    const OK            = 0;   /* Successful result */ 
    const ERROR         = 1;   /* SQL error or missing database */ 
    const INTERNAL      = 2;   /* An internal logic error in SQLite */ 
    const PERM          = 3;   /* Access permission denied */ 
    const ABORT         = 4;   /* Callback routine requested an abort */ 
    const BUSY          = 5;   /* The database file is locked */ 
    const LOCKED        = 6;   /* A table in the database is locked */ 
    const NOMEM         = 7;   /* A malloc() failed */ 
    const READONLY      = 8;   /* Attempt to write a readonly database */ 
    const INTERRUPT     = 9;   /* Operation terminated by sqlite_interrupt() */ 
    const IOERR         = 10;   /* Some kind of disk I/O error occurred */ 
    const CORRUPT       = 11;   /* The database disk image is malformed */ 
    const NOTFOUND      = 12;   /* (Internal Only) Table or record not found */ 
    const FULL          = 13;   /* Insertion failed because database is full */ 
    const CANTOPEN      = 14;   /* Unable to open the database file */ 
    const PROTOCOL      = 15;   /* Database lock protocol error */ 
    const EMPTY_TABLE   = 16;   /* ORIGINAL: EMPTY (Internal Only) Database table is empty */ 
    const SCHEMA        = 17;   /* The database schema changed */ 
    const TOOBIG        = 18;   /* Too much data for one row of a table */ 
    const CONSTRAINT    = 19;   /* Abort due to contraint violation */ 
    const MISMATCH      = 20;   /* Data type mismatch */ 
    const MISUSE        = 21;   /* Library used incorrectly */ 
    const NOLFS         = 22;   /* Uses OS features not supported on host */ 
    const AUTH          = 23;   /* Authorization denied */ 
    const NO_DB         = 26;   /* File is not a database */ 
    const ROW           = 100;  /* sqlite_step() has another row ready */ 
    const DONE          = 101;  /* sqlite_step() has finished executing */

    /**
     * {@inheritdoc}
     *
     * @param string $string
     * @param string $type (default: string) Can be string|name
     *
     * @return string
     **/
    public function quote($string, $type='string')
    {
        if ($type == 'string') {
            return "'" . str_replace("'", "''", $string) . "'";
        }

        if ($type != 'name') {
            throw new InvalidArgumentException("type has to be either string|name, not $type");
        }

        return '"' . str_replace('"', '""', $string) . '"';
    }

    /**
     * {@inheritdoc}
     *
     * @param ExpressionContract $expression
     * @param array              $bindings (optional)
     *
     * @return string
     **/
    public function render(ExpressionContract $expression, array &$bindings=[])
    {

        if ($expression instanceof KeyExpression) {
            return $this->renderKeyExpression($expression);
        }

        if ($expression instanceof Constraint) {
            return $this->renderConstraint($expression, $bindings);
        }

        if ($expression instanceof Condition) {
            return $this->renderCondition($expression, $bindings);
        }

        if (!$expression instanceof LogicalGroup) {
            throw new InvalidArgumentException("Cannot render a " . Helper::typeName($expression));
        }

        $parts = [];

        foreach ($expression->expressions() as $e) {

            if ($e instanceof LogicalGroup) {
                $parts[] = "(" . $this->render($e, $bindings) . ')';
                continue;
            }

            $parts[] = $this->render($e, $bindings);
        }

        return implode(' ' . strtoupper($expression->operator()) . ' ', $parts);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     **/
    public function name()
    {
        return 'sqlite';
    }

    /**
     * Return the timestamp format of this database.
     *
     * @return string
     **/
    public function timeStampFormat()
    {
        return 'Y-m-d H:i:s';
    }

    /**
     * {@inheritdoc}
     *
     * @param NativeError $error
     * @param Exception  $original (optional)
     *
     * @return SQLException
     **/
    public function createException(NativeError $error, Exception $original=null)
    {
        switch ($error->code) {
            case self::PERM:
            case self::READONLY:
            case self::AUTH:
            case self::CANTOPEN:
                return new SQLDeniedException($error->message, $error, 0, $original);
//             case self::ABORT: Cannot reproduce this
                // Canceled
//                 echo "\nABORT ERROR $error->code";
//                 break;
            case self::BUSY:
            case self::LOCKED:
            case self::PROTOCOL:
            case self::ROW:
                return new SQLLockException($error->message, $error, 0, $original);
            case self::IOERR:
            case self::CORRUPT:
            case self::NO_DB:
                return new SQLIOException($error->message, $error, 0, $original);
            case self::NOMEM:
            case self::FULL:
            case self::TOOBIG:
                // Cannot reproduce this in ci
                // @codeCoverageIgnoreStart
                return new SQLExceededException($error->message, $error, 0, $original);
                // @codeCoverageIgnoreEnd
            case self::CONSTRAINT:
            case self::MISMATCH:
            case self::MISUSE:
                return new SQLConstraintException($error->message, $error, 0, $original);

        }

        return $this->exceptionByMessage($error, $original);
    }


    protected function exceptionByMessage(NativeError $error, Exception $original=null)
    {
        if (Helper::contains($error->message, ['no such table'])) {
            $e = new SQLNameNotFoundException($error->message, $error, 0, $original);
            $e->missingType = 'table';
            return $e;
        }

        if (Helper::contains($error->message, ['no such column'])) {
            $e = new SQLNameNotFoundException($error->message, $error, 0, $original);
            $e->missingType = 'column';
            return $e;
        }

        if (Helper::contains($error->message, ['syntax error'])) {
            $e = new SQLSyntaxException($error->message, $error, 0, $original);
            return $e;
        }

        return new SQLException('SQL Error', $error, 0, $original);
    }

    /**
     * @return string
     **/
    public function toString()
    {
        return $this->name();
    }

    protected function renderKeyExpression(KeyExpression $e)
    {

        $key = "$e";

        if (!strpos($key, '.')) {
            return $this->quote($key, self::NAME);
        }

        $parts = array_map(function ($part) {
            return $this->quote($part, self::NAME);
        }, explode('.', $key));

        return implode('.', $parts);
    }

    protected function renderConstraint(Constraint $c, array &$bindings)
    {

        $operator = strtolower($c->operator());

        if (!isset($this->operatorMap[$operator])) {
            throw new UnsupportedParameterException("Operator of constraint \"$c\" is currently not supported");
        }

        $operator = $this->operatorMap[$operator];

        if (!$c->parameters()) {
            throw new UnsupportedParameterException("Cannot render a constraint without parameters (\"$c\" )");
        }

        $parts = [$operator];

        if ($parameters = $this->renderConstraintParameters($c->parameters(), $bindings)) {
            $parts[] = $parameters;
        }

        return implode(' ', $parts);

    }

    protected function renderCondition(Condition $c, array &$bindings)
    {

        $operand = $c->operand();
        $constraint = $c->constraint();

        $parts = [];

        if ($rendered = $this->renderAtomic($operand, $bindings)) {
            $parts[] = $rendered;
        }

        if (!$constraint) {
            return $parts[0];
        }

        if ($rendered = $this->renderAtomic($constraint, $bindings)) {
            $parts[] = $rendered;
        }

        return implode(' ', $parts);

    }

    protected function renderConstraintParameters(array $parameters, array &$bindings)
    {

        $parts = [];

        foreach ($parameters as $parameter) {

            if ($parameter instanceof ExpressionContract) {
                $parts[] = $this->render($parameter, $bindings);
                continue;
            }

            if ($rendered = $this->renderAtomic($parameter, $bindings)) {
                $parts[] = $rendered;
                continue;
            }

            if (!is_array($parameter)) {
                throw new UnsupportedParameterException('Cannot render parameter of type ' . Helper::typeName($parameter));
            }

            // Ensure numerical indexes here to not destroy the binding sequence
            $parameter = array_values($parameter);
            $last = count($parameter) - 1;

            foreach ($parameter as $i=>$sub) {

                if ($i == 0) {
                    $parts[] = '('.$this->renderAtomic($sub, $bindings);
                    continue;
                }

                if ($i == $last) {
                    $parts[] = $this->renderAtomic($sub, $bindings) . ')';
                    break;
                }

                $parts[] = $this->renderAtomic($sub, $bindings);
            }

        }

        return implode(',', $parts);
    }

    protected function renderAtomic($value, &$bindings=[])
    {
        if ($value === null) {
            return 'NULL';
        }

        if ($value instanceof DateTime) {
            $bindings[] = $value->format($this->timeStampFormat());
            return '?';
        }

        if (is_scalar($value)) {
            $bindings[] = $value;
            return '?';
        }


        if ($value instanceof ExpressionContract) {
            return $this->render($value, $bindings);
        }

        return '';

    }
}
