<?php


namespace Ems\Expression;

use Ems\Contracts\Expression\Condition as ConditionContract;
use Ems\Core\Collections\StringList;
use Ems\Core\KeyExpression;
use Ems\Core\Support\StringableTrait;
use BadMethodCallException;


/**
 * @see \Ems\Contracts\Expression\ConditionGroup
 **/
trait ConditionGroupTrait
{
    use LogicalGroupTrait;
    use ConditionalTrait;
    use StringableTrait;

    /**
     * @var bool
     **/
    protected $allowNested = true;

    /**
     * @var array
     **/
    protected $allowedOperators = [];

    /**
     * @var int
     **/
    protected $maxConditions = 0;

    /**
     * {@inheritdoc}
     *
     * @return StringList
     **/
    public function keys()
    {
        $found = [];

        foreach ($this->findExpressions(['class' => KeyExpression::class]) as $expression) {
            // Make them unique
            $found["$expression"] = $expression;
        }

        return new StringList(array_keys($found));
    }

    /**
     * {@inheritdoc}
     *
     * @param string|\Ems\Contratcs\Core\Expression $operand (optional)
     *
     * @return array
     **/
    public function conditions($operand=null)
    {
        $expressions = $this->expressions();

        if (!$operand) {
            return $expressions;
        }

        return $this->findExpressions([
            'operand'    => "$operand",
            'class'     => ConditionContract::class
        ]);
    }

    /**
     * Return true if the ConditionGroup has a condition for $operand or if none
     * passed if it has conditions at all.
     *
     * @param string|\Ems\Contratcs\Core\Expression $operand (optional)
     *
     * @return bool
     **/
    public function hasConditions($operand=null)
    {
        return (bool)count($this->conditions($operand));
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     **/
    public function toString()
    {
        return $this->renderLogicalGroupString();
    }
}
