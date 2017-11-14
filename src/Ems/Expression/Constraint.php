<?php


namespace Ems\Expression;


use BadMethodCallException;
use Ems\Contracts\Expression\Constraint as ConstraintContract;
use Ems\Core\Exceptions\UnsupportedParameterException;
use Ems\Core\Support\StringableTrait;
use InvalidArgumentException;


class Constraint implements ConstraintContract
{
    use StringableTrait;

    /**
     * @var string
     **/
    protected $name = '';

    /**
     * @var string
     **/
    protected $operator = '';


    /**
     * @var array
     **/
    protected $parameters = [];

    /**
     * @var string
     **/
    protected $toStringFormat = 'operator';

    /**
     * @var array
     **/
    protected $allowedOperators = [];

    /**
     * @param string $name
     * @param array  $parameters
     * @param string $operator (optional)
     **/
    public function __construct($name, $parameters=[], $operator='', $toStringFormat='operator')
    {
        $this->setName($name);
        $this->setParameters($parameters);
        $this->setOperator($operator);
        $this->setToStringFormat($toStringFormat);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     **/
    public function name()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     **/
    public function operator()
    {
        return $this->operator;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     **/
    public function parameters()
    {
        return $this->parameters;
    }

    /**
     * Set the name of this constraint.
     *
     * @param string $name
     *
     * @return self
     **/
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Set the parameters of this constraint.
     *
     * @param array $parameters
     *
     * @return self
     **/
    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters;
        return $this;
    }

    /**
     * Set the operator of this constraint.
     *
     * @param string $operator
     *
     * @return self
     **/
    public function setOperator($operator)
    {
        if ($this->allowedOperators && !in_array($operator, $this->allowedOperators)) {
            throw new UnsupportedParameterException('This constraint only accepts operators: ' . implode(',', $this->allowedOperators));
        }

        $this->operator = $operator;
        return $this;
    }

    /**
     * Return the toStringFormat, which is either operator or
     * name.
     * operator: = value
     * name:     equals:value
     *
     * @return string
     **/
    public function getToStringFormat()
    {
        return $this->toStringFormat;
    }

    /**
     * Set the toStringFormat, which is either operator or
     * name.
     *
     * @param string $format
     *
     * @return self
     **/
    public function setToStringFormat($format)
    {

        if (!in_array($format, ['operator', 'name'])) {
            throw new InvalidArgumentException("Format can be operator|name");
        }

        $this->toStringFormat = $format;

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
        return $this->setOperator($this->operator);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     **/
    public function toString()
    {
        if ($this->toStringFormat == 'operator') {
            return $this->renderOperatorString();
        }
        return $this->renderNameString();
    }

    /**
     * Render the operator formatted string
     *
     * @return string
     **/
    protected function renderOperatorString()
    {

        $parameters = $this->parameters ? $this->renderParameters($this->parameters) : '';

        if ($this->operator) {
            return $this->operator . ($parameters ? " $parameters" : '');
        }

        return $this->name . "($parameters)";
    }

    /**
     * Render the name formatted string
     *
     * @return string
     **/
    protected function renderNameString()
    {
        $parameters = $this->parameters ? $this->renderParameters($this->parameters) : '';

        return $parameters ? "{$this->name}:$parameters" : $this->name;
    }

    /**
     * Renders the parameters as a string
     *
     * @param array $parameters
     * @param bool  $recursion (default=false)
     *
     * @return string
     **/
    protected function renderParameters(array $parameters, $recursion=false)
    {

        $isOperatorFormat = $this->toStringFormat == 'operator';

        $separator = $isOperatorFormat ? ', ' : ',';

        $rendered = [];

        foreach ($parameters as $parameter) {

            if ($parameter === null) {
                $rendered[] = 'null';
                continue;
            }

            if (is_resource($parameter)) {
                $rendered[] = $this->toStringFormat == 'operator' ? 'resource of type ' . get_resource_type($parameter) : get_resource_type($parameter);
                continue;
            }

            if (is_object($parameter)) {
                $rendered[] = get_class($parameter);
                continue;
            }

            if (!is_array($parameter)) {
                $rendered[] = "$parameter";
                continue;
            }

            if (count($parameter) > 80  || $recursion  || !$this->containsOnlyScalars($parameter)) {
                $rendered[] = '[' . $this->renderParameters($parameter) . ']';
                continue;
            }

            if ($isOperatorFormat) {
                $rendered[] = '(' . $this->renderParameters($parameter, true) . ')';
                continue;
            }

            $rendered[] = $this->renderParameters($parameter, true);

        }

        return implode($separator, $rendered);
    }

    /**
     * @param array $values
     *
     * @return bool
     **/
    protected function containsOnlyScalars(array $values)
    {
        foreach ($values as $value) {
            if (!is_scalar($value)) {
                return false;
            }
        }
        return true;
    }

}
