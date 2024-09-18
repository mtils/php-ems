<?php


namespace Ems\Validation;

use ArrayIterator;
use Ems\Contracts\Validation\Rule as RuleContract;
use Ems\Contracts\Expression\Constraint as ConstraintContract;
use Ems\Expression\Constraint;
use Ems\Expression\ConstraintGroup;
use InvalidArgumentException;


class Rule extends ConstraintGroup implements RuleContract
{

    /**
     * @var string
     **/
    protected $toStringSeparator = '|';

    /**
     * @return int
     **/
    public function count() : int
    {
        return count($this->constraints);
    }

    /**
     * @return ArrayIterator
     **/
    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        return new ArrayIterator($this->namesToParameters());
    }

    /**
     * @param string $operator
     *
     * @return self
     **/
    public function setOperator($operator)
    {
        if ($operator != 'and') {
            throw new InvalidArgumentException("A Validation\Rule can only work as an and conjunction not $operator.");
        }
        $this->operator = $operator;
        return $this;
    }

    /**
     * Return a lookup array of constraint names and its parameters.
     * (for getIterator())
     *
     * @return array
     **/
    protected function namesToParameters()
    {
        $lookup = [];

        foreach ($this->constraints as $name=>$constraint) {
            $lookup[$name] = $constraint->parameters();
        }

        return $lookup;
    }

    /**
     * Create a new Constraint.
     *
     * @return ConstraintContract
     **/
    protected function newConstraint($name, $parameters, $operator)
    {
        return new Constraint($name, $parameters, $operator, 'name');
    }
}
