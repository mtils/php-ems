<?php


namespace Ems\Expression;

use Ems\Contracts\Core\Expression as ExpressionContract;
use Ems\Contracts\Expression\LogicalGroup as LogicalGroupContract;
use Ems\Contracts\Expression\Constraint as ConstraintContract;
use Ems\Contracts\Expression\HasExpressions;
use InvalidArgumentException;
use BadMethodCallException;
use Ems\Core\Exceptions\UnsupportedParameterException;
use OutOfBoundsException;

/**
 * @see \Ems\Contracts\Expression\LogicalGroup
 **/
trait LogicalGroupTrait
{

    /**
     * @var string
     **/
    protected $_operator = 'and';

    /**
     * @var string
     **/
    protected $_toStringSeparator = ' AND ';

    /**
     * @var array
     **/
    protected $_expressions = [];

    /**
     * @var array
     **/
    protected $_supportedOperators = ['and', 'or', 'nand', 'nor'];

    /**
     * @var bool
     **/
    protected $_allowMultipleConnectives = true;

    /**
     * @var array
     **/
    protected $_allowedConnectives = [];

    /**
     * @var bool
     **/
    protected $_allowNesting = true;

    /**
     * @var array
     **/
    protected $_allowedOperators = [];

    /**
     * @var int
     **/
    protected $_maxConditions = 0;

    /**
     * Return the operator (AND|OR|NOT|NOR|NAND).
     *
     * @return string (AND|OR|NOT|NOR|NAND)
     **/
    public function operator()
    {
        return $this->_operator;
    }

    /**
     * Return all expressions
     *
     * @return array
     **/
    public function expressions()
    {
        return $this->_expressions;
    }

    /**
     * Add an expression.
     *
     * @param ExpressionContract $expression
     *
     * @return self
     **/
    public function add(ExpressionContract $expression)
    {
        $this->typeCheck($expression);
        $this->applyRestrictions($expression);
        $this->_expressions[] = $expression;
        return $this;
    }

    /**
     * Remove an expression.
     *
     * @param ExpressionContract $expression
     *
     * @return self
     **/
    public function remove(ExpressionContract $expression)
    {
        $filtered = array_filter($this->_expressions, function ($known) use ($expression) {
            return "$known" != "$expression";
        });

        $this->_expressions = array_values($filtered);

        return $this;
    }

    /**
     * Remove all expressions.
     *
     * @return self
     **/
    public function clear()
    {
        $this->_expressions = [];
        return $this;
    }

    /**
     * @param string $operator
     *
     * @return self
     **/
    public function setOperator($operator)
    {
        if (!in_array($operator, $this->_supportedOperators)) {
            $list = implode('|', $this->_supportedOperators);
            throw new InvalidArgumentException("operator has to be $list, not $operator");
        }

        if ($this->_allowedConnectives && !in_array($operator, $this->_allowedConnectives)) {
            throw new UnsupportedParameterException("This logical group only accept connectives:" . implode($this->_allowedConnectives));
        }
        $this->_operator = $operator;
        $this->_toStringSeparator = ' ' . strtoupper($operator) . ' ';
        return $this;
    }

    /**
     * Return the allowed connectives (operators)
     *
     * @return array
     **/
    public function allowedConnectives()
    {
        return $this->_allowedConnectives;
    }

    /**
     * Restrict the supported connectives (logical operators) to the passed
     * connectives. This can only be done once.
     *
     * @param string|array $connectives
     *
     * @return self
     **/
    public function allowConnectives($connectives)
    {
        $connectives = is_array($connectives) ? $connectives : func_get_args();
        if ($this->_allowedConnectives && $connectives != $this->_allowedConnectives) {
            throw new BadMethodCallException('You can only set the allowed connectives once.');
        }
        $this->_allowedConnectives = $connectives;
        $this->checkRestrictions();
        return $this;
    }

    /**
     * Is a change from or to and or any other connective allowed
     *
     * @return bool
     **/
    public function areMultipleConnectivesAllowed()
    {
        if (count($this->_allowedConnectives) == 1) {
            return false;
        }
        return $this->_allowMultipleConnectives;
    }

    /**
     * Let this group (and all subgroups) only have one connective.
     * So if the operator of this one is "or", there will be no chance to
     * add groups with another operator.
     *
     * @return self
     **/
    public function forbidMultipleConnectives()
    {
        $this->_allowMultipleConnectives = false;
        $this->checkRestrictions();
        return $this;
    }

    /**
     * Is it allow to add sub groups?
     *
     * @return bool
     **/
    public function isNestingAllowed()
    {
        return $this->_allowNesting;
    }

    /**
     * Dont allow sub logical groups.
     *
     * @return self
     **/
    public function forbidNesting()
    {
        $this->_allowNesting = false;
        $this->checkRestrictions();
        return $this;
    }

    /**
     * Return the allowed CONSTRAINT operators.
     *
     * @return array
     **/
    public function allowedOperators()
    {
        return $this->_allowedOperators;
    }

    /**
     * Force the CONSTRAINTS to only support the passed operator(s).
     *
     * @param array|string $operators
     *
     * @return self
     **/
    public function allowOperators($operators)
    {
        if ($this->_allowedOperators) {
            throw new BadMethodCallException('You can only set the allowed operators once.');
        }
        $this->_allowedOperators = is_array($operators) ? $operators : func_get_args();
        $this->checkRestrictions();
        return $this;
    }

    /**
     * Return the maximum amount of conditions added to this group.
     *
     * @return int
     **/
    public function maxConditions()
    {
        return $this->_maxConditions;
    }

    /**
     * Restrict the maximum number of conditions added to this group.
     * CAUTION Because of the complex effects of this restriction nesting is
     * automatically forbidden if setting some max conditions.
     *
     * @param int $max
     *
     * @return self
     **/
    public function allowMaxConditions($max)
    {
        if ($this->_maxConditions) {
            throw new BadMethodCallException('You can only set the maximum conditions only once.');
        }
        $this->_maxConditions = $max;
        $this->_allowNesting = false;
        $this->checkRestrictions();
        return $this;
    }

    /**
     * This is just a method to do some custom type checking before
     * adding it to the LogicalGroup.
     *
     * @param ExpressionContract $expression
     **/
    protected function typeCheck(ExpressionContract $expression)
    {
    }

    /**
     * Check the applied restrictions on $expression. allowedConnectives,
     * allowedOperators, ....
     *
     * @param ExpressionContract $expression
     * @param bool               $beforeAdding (default:true)
     **/
    protected function applyRestrictions(ExpressionContract $expression, $beforeAdding=true)
    {

        if ($beforeAdding && $this->_maxConditions && count($this->_expressions) >= $this->_maxConditions) {
            throw new OutOfBoundsException("This group can only hold a maximum of {$this->_maxConditions} conditions.");
        }

        if ($this->_allowedOperators && ($expression instanceof Constraint || $expression instanceof Condition)) {
            $expression->allowOperators($this->_allowedOperators);
        }

        if ($expression instanceof LogicalGroupContract) {
            $this->applyGroupRestrictions($expression);
        }

        if (!$beforeAdding && $this->_maxConditions && count($this->_expressions) > $this->_maxConditions) {
            throw new OutOfBoundsException("This group can only hold a maximum of {$this->_maxConditions} conditions.");
        }
    }

    protected function applyGroupRestrictions(LogicalGroupContract $group)
    {
        if (!$this->_allowNesting) {
            throw new UnsupportedParameterException("This logical group does not allow nested groups");
        }

        if ($this->_allowedConnectives && !in_array($group->operator(), $this->_allowedConnectives)) {
            throw new UnsupportedParameterException("This logical group only accept connectives:" . implode($this->_allowedConnectives));
        }

        if ($this->_allowedOperators) {
            $group->allowOperators($this->_allowedOperators);
        }

        if ($this->_allowMultipleConnectives) {
            return;
        }

        if ($group->operator() != $this->operator()) {
            $comparison = $group->operator() . ' != '. $this->operator();
            throw new UnsupportedParameterException("This logical group forbids multiple connectives ($comparison)");
        }

        $group->allowConnectives($this->operator());
    }

    protected function checkRestrictions()
    {
        foreach ($this->_expressions as $expression) {
            $this->applyRestrictions($expression, false);
        }
    }

    /**
     * Find expressions by its name, string representation, operator, class or operand.
     *
     * @param array $attributes
     * @param array $expressions (optional)
     *
     * @return array
     **/
    protected function findExpressions(array $attributes, $expressions=null)
    {

        $search = [
            'string'   => isset($attributes['string'])   ? $attributes['string']   : '*',
            'name'     => isset($attributes['name'])     ? $attributes['name']     : '*',
            'operator' => isset($attributes['operator']) ? $attributes['operator'] : '*',
            'operand'  => isset($attributes['operand'])  ? $attributes['operand']  : '*',
            'class'    => isset($attributes['class'])    ? $attributes['class']    : '*',
        ];

        $expressions = $expressions ?: $this->allExpressions();

        $matches = [];

        foreach ($expressions as $expression) {

            if ($this->matchesAttributes($expression, $search)) {
                $matches[] = $expression;
            }
        }

        return $matches;
    }

    /**
     * Recursively collect all expressions.
     *
     * @param array $expressions (optional)
     * @param array $all (optional)
     *
     * @return array
     **/
    protected function allExpressions(array $expressions=null, &$all=null)
    {

        $expressions = $expressions ?: $this->expressions();
        $all = $all ?: [];

        foreach ($expressions as $expression) {

            $all[] = $expression;

            if ($expression instanceof HasExpressions) {
                $this->allExpressions($expression->expressions(), $all);
            }

        }

        return $all;
    }

    /**
     * Check if $expression matches $search. Query only the objects which have
     * the passed criteria methods/keys by passing a class.
     *
     * @param ExpressionContract $expression
     * @param array              $search
     *
     * @return bool
     **/
    protected function matchesAttributes(ExpressionContract $expression, array $search)
    {

        if ($search['class'] !== '*' && !$expression instanceof $search['class']) {
            return false;
        }

        if ($search['name'] !== '*' && $expression->name() != $search['name']) {
            return false;
        }

        if ($search['operator'] !== '*' && $expression->operator() != $search['operator']) {
            return false;
        }

        if ($search['operand'] !== '*' && (string)$expression->operand() != $search['operand']) {
            return false;
        }

        if ($search['string'] !== '*' && "$expression" != $search['string']) {
            return false;
        }

        return true;

    }

    /**
     * {@inheritdoc}
     *
     * @return string
     **/
    protected function renderLogicalGroupString()
    {
        $parts = [];

        foreach ($this->expressions() as $expression) {

            if ($expression instanceof LogicalGroupContract) {
                $parts[] = '(' . "$expression" . ')';
                continue;
            }

            $parts[] = $expression;

        }

        return implode($this->_toStringSeparator, $parts);
    }

}
