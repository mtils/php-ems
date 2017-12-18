<?php


namespace Ems\Expression;

use Ems\Contracts\Core\Expression as ExpressionContract;
use Ems\Contracts\Expression\Condition as ConditionContract;
use Ems\Contracts\Expression\ConditionGroup as ConditionGroupContract;
use Ems\Contracts\Expression\Constraint as ConstraintContract;
use Ems\Contracts\Expression\ConstraintGroup as ConstraintGroupContract;
use Ems\Core\Exceptions\MissingArgumentException;
use Ems\Core\Exceptions\NotImplementedException;
use Ems\Core\Exceptions\UnsupportedParameterException;
use Ems\Core\Expression;
use Ems\Core\KeyExpression;
use Ems\Contracts\Core\Type;
use InvalidArgumentException;
use Closure;


/**
 * This trait assumes its added to a LogicalGroup implementing class.
 *
 * @see \Ems\Contracts\Expression\Conditional
 **/
trait ConditionalTrait
{

    /**
     * @var array
     **/
    protected $_operatorNames = [
        '='         => 'equal',
        '!='        => 'unequal',
        '<>'        => 'unequal',
        '!'         => 'not',
        '<'         => 'smaller',
        '>'         => 'greater',
        '>='        => 'min',
        '<='        => 'max',
        'is'        => 'same',
        'is not'    => 'not same'
    ];

    /**
     * {@inheritdoc}
     *
     * @param string|\Ems\Contracts\Core\Expression|\Closure $operand
     * @param mixed                                           $operatorOrValue (optional)
     * @param mixed                                           $value
     *
     * @return self
     **/
    public function where($operand, $operatorOrValue=null, $value=null)
    {
        return $this->addWhere($operand, $operatorOrValue, $value, 'and', func_num_args());
    }

    /**
     * {@inheritdoc}
     *
     * @param string|\Ems\Contracts\Core\Expression|\Closure $operand
     * @param mixed                                           $operatorOrValue (optional)
     * @param mixed                                           $value
     *
     * @return self
     *
     * @see self::where()
     **/
    public function orWhere($operand, $operatorOrValue=null, $value=null)
    {
        return $this->addWhere($operand, $operatorOrValue, $value, 'or', func_num_args());
    }

    /**
     * {@inheritdoc}
     *
     * @param string|\Ems\Contracts\Core\Expression|\Closure $operand
     * @param mixed                                           $operatorOrValue (optional)
     * @param mixed                                           $value
     *
     * @return self
     *
     * @see self::where()
     **/
    public function whereNot($operand, $operatorOrValue=null, $value=null)
    {
        return $this->addWhere($operand, $operatorOrValue, $value, 'nand', func_num_args());
    }

    /**
     * {@inheritdoc}
     *
     * @param string|\Ems\Contracts\Core\Expression|\Closure $operand
     * @param mixed                                           $operatorOrValue (optional)
     * @param mixed                                           $value
     *
     * @return self
     *
     * @see self::where()
     **/
    public function whereNone($operand, $operatorOrValue=null, $value=null)
    {
        return $this->addWhere($operand, $operatorOrValue, $value, 'nor', func_num_args());
    }

    /**
     * @param string|\Ems\Contracts\Core\Expression|\Closure $operand
     * @param mixed                                           $operatorOrValue (optional)
     * @param mixed                                           $value
     * @param string                                          $boolean
     * @param int                                             $argCount
     *
     * @return static
     */
    protected function addWhere($operand, $operatorOrValue, $value, $boolean, $argCount)
    {
        $expressionCount = count($this->expressions());

        // If the conditiongroup was empty, set the operator
        if ($expressionCount === 0) {
            $this->setOperator($boolean);
        }

        $prevOperator = $this->operator();

        $condition = $this->toCondition($operand, $operatorOrValue, $value, $argCount);

        // If the operator does not change or less then 2 conditions where
        // added, just add the condition
        if ($prevOperator == $boolean  || $expressionCount < 2) {
            return $this->copyForChaining($this->buildConditions($condition), $boolean);
        }

        // The operator did change and it has more than 1 conditions

        $copy = $this->newConditionGroup($this->buildConditions(), $this->operator());

        // Create a new group with all old conditions as a ConditionGroup
        // and the new one
        return $this->copyForChaining([$copy, $condition], $boolean);

    }

    protected function toCondition($operand, $operatorOrValue, $value, $argCount)
    {

        $expression = $this->operandToExpression($operand);


        if ($expression instanceof ConditionContract || $expression instanceof ConditionGroupContract) {
            return $expression;
        }

        if ($operatorOrValue === null && $argCount == 2) {
            $operatorOrValue = 'is';
            $value = null;
            $argCount = 3;
        }

        if (is_array($operatorOrValue) && $argCount == 2) {
            $value = $operatorOrValue;
            $operatorOrValue = 'in';
            $argCount = 3;
        }

        if (!$operatorOrValue) {
            throw new MissingArgumentException('If you dont pass a Condition, closure or ConditionGroup you have to pass a second argument.');
        }

        $constraint = $this->toConstraint($operatorOrValue, $value, $argCount);

        // This will never happen. where() triggers a fork, the
        // fork forces its constraints.
        // add() is not handled by this class
//         if ($this->_allowedOperators) {
//             $constraint->allowOperators($this->_allowedOperators);
//         }

        return $this->newCondition($expression, $constraint);

    }

    protected function operandToExpression($operand)
    {

        if ($operand instanceof ExpressionContract) {
            return $operand;
        }

        if (is_numeric($operand)) {
            return new Expression($operand);
        }


        if (is_string($operand) || method_exists($operand, '__toString')) {
            return new KeyExpression("$operand");
        }

        if (!$operand instanceof Closure) {
            throw new InvalidArgumentException('Unknown operand type: ' . Type::of($operand));
        }

        return $operand($this->newClosureGroup());

    }

    protected function toConstraint($operatorOrValue, $passedValue, $argCount)
    {

        if ($operatorOrValue instanceof ConstraintGroupContract || $operatorOrValue instanceof ConstraintContract) {
            return $operatorOrValue;
        }

        $value = $argCount > 2 ? $passedValue : $operatorOrValue;
        $operator = $argCount > 2 ? $operatorOrValue : '=';

        return $this->newConstraint($this->operatorName($operator), [$value], $operator);

    }

    /**
     * @param array $conditions
     * @param $boolean
     *
     * @return static
     */
    protected function copyForChaining(array $conditions, $boolean)
    {
        $allowedConnectives = $this->allowedConnectives();

        if ($allowedConnectives && !in_array($boolean, $allowedConnectives)) {
            throw new UnsupportedParameterException("This logical group only accept connectives:" . implode($allowedConnectives));
        }

        $fork = $this->fork($conditions, $boolean);

        if ($allowedConnectives) {
            $fork->allowConnectives($allowedConnectives);
        }

        if (!$this->_allowMultipleConnectives) {
//             $fork->allowConnectives($this->operator());
            $fork->forbidMultipleConnectives();
        }

        if ($this->_allowedOperators && !$fork->allowedOperators()) {
            $fork->allowOperators($this->_allowedOperators);
        }

        if ($this->_maxConditions) {
            $fork->allowMaxConditions($this->_maxConditions);
        }

        return $fork;
    }

    /**
     * @param array $conditions
     * @param $boolean
     *
     * @return static
     *
     * @throws NotImplementedException
     */
    protected function fork(array $conditions, $boolean)
    {
        throw new NotImplementedException('You have to implement fork to work with ConditionalTrait');
    }

    protected function buildConditions($newCondition=null)
    {
        $conditions = $this->expressions();
        if ($newCondition) {
            $conditions[] = $newCondition;
        }
        return $conditions;
    }

    protected function newClosureGroup()
    {
        return new ConditionGroup;
    }

    protected function newConditionGroup(array $conditions, $boolean)
    {
        return new ConditionGroup($conditions, $boolean);
    }

    protected function newCondition(ExpressionContract $operand, $constraint)
    {
        return new Condition($operand, $constraint);
    }

    /**
     * @param string $name
     * @param array  $parameters
     * @param string $operator
     *
     * @return ConstraintContract
     **/
    protected function newConstraint($name, $parameters=[], $operator)
    {
        return new Constraint($name, (array)$parameters, $operator);
    }

    /**
     * Try to find a nice operator name.
     *
     * @param string $operator
     *
     * @return string
     **/
    protected function operatorName($operator)
    {
        if (isset($this->_operatorNames[$operator])) {
            return $this->_operatorNames[$operator];
        }
        return strtolower($operator);
    }
    

}
