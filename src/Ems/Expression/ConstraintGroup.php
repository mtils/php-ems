<?php


namespace Ems\Expression;

use Ems\Contracts\Core\Expression as ExpressionContract;
use Ems\Contracts\Core\Type;
use Ems\Contracts\Expression\Constraint as ConstraintContract;
use Ems\Contracts\Expression\ConstraintGroup as ConstraintGroupContract;
use Ems\Core\Exceptions\KeyNotFoundException;
use Ems\Core\Support\StringableTrait;
use InvalidArgumentException;

class ConstraintGroup implements ConstraintGroupContract
{
    use StringableTrait;
    use ConstraintParsingMethods;

    /**
     * @var array
     **/
    protected $constraints = [];

    /**
     * @var string
     **/
    protected $operator = 'and';

    /**
     * @var string
     **/
    protected $toStringSeparator = ' AND ';


    /**
     * @var array
     **/
    protected $supportedOperators = ['and', 'or', 'nand', 'nor'];

    /**
     * @param array|string $definition (optional)
     **/
    public function __construct($definition=null)
    {
        if ($definition) {
            $this->fill($definition);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return string (AND|OR|NOT|NOR|NAND)
     **/
    public function operator()
    {
        return $this->operator;
    }

    /**
     * Return all expressions
     *
     * @return array
     **/
    public function expressions()
    {
        return array_values($this->constraints);
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
        $this->checkType($expression);
        $this->constraints[$expression->name()] = $expression;
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
        $this->checkType($expression);
        unset($this->constraints[$expression->name()]);
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     **/
    public function constraints()
    {
        return $this->constraints;
    }

    /**
     * {@inheritdoc}
     *
     * @param array|string $definition
     *
     * @return self
     **/
    public function fill($definition)
    {
        $this->clear();
        $this->merge($definition);
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param array|string $definition
     *
     * @return self
     **/
    public function merge($definition)
    {
        $constraints = $this->explodeConstraints($definition);

        foreach ($constraints as $index=>$definition) {

            list($name, $parameters) = is_array($definition) ?
                                       [$index, $definition] :
                                       $this->nameAndParameters($definition);

            $constraint = $this->newConstraint(
                Type::snake_case($name),
                $parameters,
                ''
            );
            $this->constraints[$constraint->name()] = $constraint;

        }
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return self
     **/
    public function clear()
    {
        $this->constraints = [];
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $name
     *
     * @return array
     **/
    public function __get($name)
    {

        $name = Type::snake_case($name);

        if (!$this->__isset($name)) {
            throw new KeyNotFoundException("No constraint with key $name");
        }

        $parameters = $this->constraints[$name]->parameters();

        $count = count($parameters);

        // For easier access return just null if no parameters were set
        if ($count == 0) {
            return null;
        }

        // For easier access return just the first parameter
        if ($count == 1) {
            return $parameters[0];
        }

        return $parameters;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $name
     * @param mixed  $parameters
     *
     * @return void
     **/
    public function __set($name, $parameters)
    {
        $constraint = $this->newConstraint(
            Type::snake_case($name),
            (array)$parameters,
            ''
        );

        $this->constraints[$constraint->name()] = $constraint;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $name
     *
     * @return bool
     **/
    public function __isset($name)
    {
        $name = Type::snake_case($name);
        return isset($this->constraints[$name]);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $name
     *
     * @return void
     **/
    public function __unset($name)
    {
        $name = Type::snake_case($name);
        unset($this->constraints[$name]);
    }

    /**
     * Check if $offset exists.
     *
     * @param mixed $offset
     *
     * @return bool
     **/
    public function offsetExists($offset)
    {
        return $this->__isset($offset);
    }

    /**
     * Get value of $offset.
     *
     * @param mixed $offset
     *
     * @return mixed
     **/
    public function offsetGet($offset)
    {
        return $this->constraints[$offset];
    }

    /**
     * Set the value of $offset.
     *
     * @param mixed $offset
     * @param mixed $value
     **/
    public function offsetSet($offset, $value)
    {
        $this->checkType($value);
        if ($offset != $value->name()) {
            throw new InvalidArgumentException("The offset has to be the name of the constraint");
        }
        $this->constraints[$value->name()] = $value;
    }

    /**
     * Unset $offset.
     *
     * @param mixed $offset
     **/
    public function offsetUnset($offset)
    {
        $this->__unset($offset);
    }

    /**
     * @param string $operator
     *
     * @return self
     **/
    public function setOperator($operator)
    {
        if (!in_array($operator, $this->supportedOperators)) {
            $list = implode('|', $this->supportedOperators);
            throw new InvalidArgumentException("operator has to be $list, not $operator");
        }
        $this->operator = $operator;
        $this->toStringSeparator = ' ' . strtoupper($operator) . ' ';
        return $this;
    }

     /**
     * Returns a rendered version of the constraints.
     *
     * @return string
     **/
    public function toString()
    {
        $constraints = [];
        foreach ($this->constraints as $name=>$constraint) {
            $constraints[] = "$constraint";
        }
        return implode($this->toStringSeparator, $constraints);
    }

    /**
     * Throws an error if the expression is not a constraint.
     *
     * @param ExpressionContract $expression
     *
     * @return ConstraintContract
     */
    protected function checkType(ExpressionContract $expression)
    {
        if (!$expression instanceof ConstraintContract) {
            throw new InvalidArgumentException("ConstraintGroup works only with Constraint not " . Type::of($expression));
        }

        return $expression;
    }

    /**
     * Create a new Constraint.
     *
     * @return ConstraintContract
     **/
    protected function newConstraint($name, $parameters, $operator)
    {
        return new Constraint($name, $parameters, $operator, 'operator');
    }

}
