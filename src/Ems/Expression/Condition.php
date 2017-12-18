<?php


namespace Ems\Expression;

use BadMethodCallException;
use Ems\Contracts\Core\Expression as ExpressionContract;
use Ems\Contracts\Expression\Condition as ConditionContract;
use Ems\Contracts\Expression\Constraint as ConstraintContract;
use Ems\Contracts\Expression\ConstraintGroup as ConstraintGroupContract;
use Ems\Core\Support\StringableTrait;
use Ems\Contracts\Core\Type;
use InvalidArgumentException;


class Condition implements ConditionContract
{
    use StringableTrait;

    /**
     * @var string|\Ems\Contracts\Core\Expression
     **/
    protected $operand;


    /**
     * @var ConstraintContract
     **/
    protected $constraint;

    /**
     * @var array
     **/
    protected $allowedOperators = [];

    /**
     * @param string|ExpressionContract           $operand (optional)
     * @param ConstraintContract|ConstraintGroup  $constraint (optional)
     **/
    public function __construct($operand=null, $constraint=null)
    {
        if ($operand) {
            $this->setOperand($operand);
        }
        if ($constraint) {
            $this->setConstraint($constraint);
        }
    }

     /**
     * {@inheritdoc}
     *
     * @return mixed|ExpressionContract
     **/
    public function operand()
    {
        return $this->operand;
    }

    /**
     * {@inheritdoc}
     *
     * @return ConstraintContract|ConstraintGroupContract
     **/
    public function constraint()
    {
        return $this->constraint;
    }

    /**
     * @param string|ExpressionContract $operand
     *
     * @return self
     **/
    public function setOperand($operand)
    {
        $this->operand = $this->checkOperand($operand);
        return $this;
    }

    /**
     * Return all expressions
     *
     * @return array
     **/
    public function expressions()
    {

        $expressions = [];

        if ($this->operand instanceof ExpressionContract) {
            $expressions[] = $this->operand;
        }

        if ($this->constraint instanceof ExpressionContract) {
            $expressions[] = $this->constraint;
        }

        return $expressions;

    }

    /**
     * @param ConstraintContract|ConstraintGroupContract $constraint
     *
     * @return self
     **/
    public function setConstraint($constraint)
    {
        $this->constraint = $this->checkConstraint($constraint);
        return $this;
    }

    /**
     * Return the allowed operators of this constraint.
     *
     * @return array
     **/
    public function allowedOperators()
    {
        return $this->allowedOperators;
    }

    /**
     * Force the constraint to only support the passed operator(s).
     *
     * @param array|string $operators
     *
     * @return self
     **/
    public function allowOperators($operators)
    {
        $operators = is_array($operators) ? $operators : func_get_args();

        if ($this->allowedOperators && $this->allowedOperators != $operators) {
            throw new BadMethodCallException('You can only set the allowed operators once.');
        }

        $this->allowedOperators = $operators;

        if ($this->constraint) {
            $this->checkConstraint($this->constraint);
        }

        return $this;
    }

    /**
     * Checks the type of the operand
     *
     * @param mixed $operand
     *
     * @return mixed
     **/
    protected function checkOperand($operand)
    {
        if (!is_scalar($operand) && !$operand instanceof ExpressionContract) {
            throw new InvalidArgumentException('Condition only acceps scalars and Expression, not ' . Type::of($operand));
        }
        return $operand;
    }

    /**
     * Checks the type of the constraint
     *
     * @param mixed $constraint
     *
     * @return ConstraintContract|ConstraintGroupContract
     **/
    public function checkConstraint($constraint)
    {
        if (!$constraint instanceof ConstraintContract && !$constraint instanceof ConstraintGroupContract) {
            throw new InvalidArgumentException("Constraint has to be Constraint or ConstraintGroup, not " . Type::of($constraint));
        }
        if ($this->allowedOperators && $constraint instanceof Constraint) {
            $constraint->allowOperators($this->allowedOperators);
        }
        return $constraint;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     **/
    public function toString()
    {
        $parts = [];

        if ($this->operand) {
            $parts[] = (string)$this->operand;
        }

        if ($this->constraint) {
            $parts[] = (string)$this->constraint;
        }

        return implode(' ', $parts);
    }
}
