<?php

/**
 *  * Created by mtils on 23.05.19 at 11:35.
 **/

namespace Ems\Model\Database\Dialects;

use DateTime;
use Ems\Contracts\Core\Expression as ExpressionContract;
use Ems\Contracts\Core\StringableTrait;
use Ems\Contracts\Core\Type;
use Ems\Contracts\Expression\Condition;
use Ems\Contracts\Expression\Constraint;
use Ems\Contracts\Expression\LogicalGroup;
use Ems\Contracts\Model\Database\Dialect;
use Ems\Contracts\Model\Database\NativeError;
use Ems\Contracts\Model\Database\SQLException;
use Ems\Core\Exceptions\UnsupportedParameterException;
use Ems\Core\Helper;
use Ems\Core\KeyExpression;
use Ems\Model\Database\SQLNameNotFoundException;
use Ems\Model\Database\SQLSyntaxException;
use Exception;

use function explode;
use function strpos;

abstract class AbstractDialect implements Dialect
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
     * Quote a string or table/column/database name
     *
     * @param string $string
     * @param string $type (default: string) Can be string|name
     *
     * @return string
     **/
    public function quote($string, $type = 'string')
    {
        if ($type === 'string') {
            return $this->quoteString($string);
        }

        if (!strpos($string, '.')) {
            return $this->quoteName($string, $type);
        }

        $parts = explode('.', $string);
        $segments = [];

        foreach ($parts as $segment) {
            $segments[] = $this->quoteName($segment, $type);
        }

        return implode('.', $segments);
    }


    /**
     * {@inheritdoc}
     *
     * @param ExpressionContract $expression
     * @param array              $bindings (optional)
     *
     * @return string
     **/
    public function render(ExpressionContract $expression, array &$bindings = [])
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
            return $expression->toString();
//            throw new InvalidArgumentException("Cannot render a " . Type::of($expression));
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
     * @param NativeError $error
     * @param Exception  $original (optional)
     *
     * @return SQLException
     **/
    public function createException(NativeError $error, Exception $original = null)
    {
        return $this->exceptionByMessage($error, $original);
    }

    /**
     * @param string $string
     *
     * @return string
     */
    abstract protected function quoteString($string);

    /**
     * @param string $name
     * @param string $type
     *
     * @return string
     */
    abstract protected function quoteName($name, $type = 'name');

    protected function exceptionByMessage(NativeError $error, Exception $original = null)
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
                throw new UnsupportedParameterException('Cannot render parameter of type ' . Type::of($parameter));
            }

            // Ensure numerical indexes here to not destroy the binding sequence
            $parameter = array_values($parameter);
            $last = count($parameter) - 1;

            foreach ($parameter as $i => $sub) {
                if ($i == 0) {
                    $parts[] = '(' . $this->renderAtomic($sub, $bindings);
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

    protected function renderAtomic($value, &$bindings = [])
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

    /**
     * Quote each identifier if the string contains many.
     *
     * @param $string
     *
     * @return string
     */
    protected function quoteEachSegment($string)
    {
        if (!strpos($string, '.')) {
            return $this->quote($string, Dialect::NAME);
        }

        $parts = explode('.', $string);
    }
}
