<?php
/**
 *  * Created by mtils on 15.03.2022 at 10:12.
 **/

namespace Ems\Contracts\Validation;

use ArrayIterator;
use Ems\Core\Exceptions\KeyNotFoundException;
use Ems\Core\Exceptions\NotImplementedException;
use JsonSerializable;
use RuntimeException;
use Throwable;

class ValidationException extends RuntimeException implements Validation, JsonSerializable
{
    /**
     * @var array
     **/
    protected $failures = [];

    /**
     * @var array
     **/
    protected $rules = [];

    /**
     * @var string
     **/
    protected $validatorClass = '';

    public function __construct(array $failures = [], array $rules = [], string $validatorClass = null,
                                string $message='Validation errors occurred', int $code=4220, Throwable $previous=null)
    {
        parent::__construct($message, $code, $previous);
        $this->failures = $failures;
        $this->rules = $rules;
        $this->validatorClass = $validatorClass;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $key
     * @param string $rule
     * @param array  $parameters (optional)
     *
     * @return ValidationException
     **/
    public function addFailure($key, $ruleName, array $parameters = []) : ValidationException
    {
        if (!isset($this->failures[$key])) {
            $this->failures[$key] = [];
        }

        $this->failures[$key][$ruleName] = $parameters;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     **/
    public function failures() : array
    {
        return $this->failures;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $key
     * @param string $rule
     *
     * @return array
     **/
    public function parameters($key, $ruleName) : array
    {
        if (!isset($this->failures[$key])) {
            throw new KeyNotFoundException("Key '$key' has no failures");
        }

        if (!isset($this->failures[$key][$ruleName])) {
            throw new KeyNotFoundException("Key '$key' has no failed rule '$ruleName'");
        }

        return $this->failures[$key][$ruleName];
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     **/
    public function rules() : array
    {
        return $this->rules;
    }

    /**
     * @param array $rules
     *
     * @return self
     **/
    public function setRules(array $rules) : ValidationException
    {
        $this->rules = $rules;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     **/
    public function validatorClass() : string
    {
        return $this->validatorClass;
    }

    /**
     * @param string $validatorClass
     *
     * @return self
     **/
    public function setValidatorClass(string $validatorClass) : ValidationException
    {
        $this->validatorClass = $validatorClass;

        return $this;
    }

    /**
     * @see JsonSerializable
     **/
    public function jsonSerialize() : array
    {
        return [
            'failures' => $this->failures(),
            'rules' => $this->rules(),
            'validator_class' => $this->validatorClass(),
        ];
    }

    /**
     * @param array $data
     *
     * @return self
     **/
    public function fill(array $data) : ValidationException
    {
        if (isset($data['failures'])) {
            $this->failures = $data['failures'];
        }

        if (isset($data['rules'])) {
            $this->setRules($data['rules']);
        }

        if (isset($data['validator_class'])) {
            $this->setValidatorClass($data['validator_class']);
        }

        return $this;
    }

    /**
     * @param string $key
     *
     * @return bool
     **/
    public function offsetExists($key)
    {
        return isset($this->failures[$key]);
    }

    /**
     * @param string $key
     *
     * @return array
     **/
    public function offsetGet($key)
    {
        return $this->failures[$key];
    }

    /**
     * Setting a rule directly is not supported.
     *
     * @param string $key
     * @param array  $value
     **/
    public function offsetSet($key, $value)
    {
        throw new NotImplementedException('Validation does not support array set access. Use addFailure instead');
    }

    /**
     * Remove all failures of $key.
     *
     * @param string $key
     **/
    public function offsetUnset($key)
    {
        unset($this->failures[$key]);
    }

    /**
     * @return ArrayIterator
     **/
    public function getIterator() : ArrayIterator
    {
        return new ArrayIterator($this->failures);
    }

    /**
     * Count returns the amount of failures, not keys. So dont use
     * it in loops using indexes/numbers with count.
     *
     * @return int
     **/
    public function count() : int
    {
        $count = 0;

        foreach ($this->failures as $key => $failures) {
            foreach ($failures as $ruleName => $parameters) {
                ++$count;
            }
        }

        return $count;
    }
}