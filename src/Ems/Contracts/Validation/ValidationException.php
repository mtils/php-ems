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

use function implode;

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

    /**
     * @var bool
     */
    protected $failuresInConstruct = false;

    /**
     * @var bool
     */
    protected $wasManipulated = false;

    /**
     * @var string
     */
    protected $originalMessage = '';

    /**
     * @var array
     */
    protected $customMessages = [];

    public function __construct(array $failures = [], array $rules = [], string $validatorClass = null,
                                string $message='', int $code=4220, Throwable $previous=null)
    {
        parent::__construct($this->generateMessage($message, $failures), $code, $previous);
        $this->originalMessage = $message;
        $this->failures = $failures;
        $this->failuresInConstruct = (bool)$failures;
        $this->rules = $rules;
        $this->validatorClass = $validatorClass ?: '';
    }

    /**
     * {@inheritdoc}
     *
     * @param string $key
     * @param string $rule
     * @param array  $parameters (optional)
     * @param string|null   $customMessage
     *
     * @return ValidationException
     **/
    public function addFailure(string $key, string $ruleName, array $parameters = [], string $customMessage=null) : ValidationException
    {
        if (!isset($this->failures[$key])) {
            $this->failures[$key] = [];
        }

        $this->failures[$key][$ruleName] = $parameters;
        $this->wasManipulated = true;
        if ($customMessage === null) {
            return $this;
        }
        if (!isset($this->customMessages[$key])) {
            $this->customMessages[$key] = [];
        }
        $this->customMessages[$key][$ruleName] = $customMessage;
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
    public function parameters(string $key, string $ruleName) : array
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
     * {@inheritDoc}
     *
     * @param string $key
     * @param string $ruleName
     * @return string|null
     */
    public function customMessage(string $key, string $ruleName): ?string
    {
        return $this->customMessages[$key][$ruleName] ?? null;
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
        $this->wasManipulated = true;
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
        $this->wasManipulated = true;
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
        $this->wasManipulated = true;
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
        $this->wasManipulated = true;
        unset($this->failures[$key]);
    }

    /**
     * @return ArrayIterator
     **/
    #[\ReturnTypeWillChange]
    public function getIterator() : ArrayIterator
    {
        return new ArrayIterator($this->failures);
    }

    /**
     * Count returns the amount of failures, not keys. So don't use
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

    /**
     * @return Validation
     */
    public function copy() : Validation
    {
        return new static(
            $this->failures(),
            $this->rules(),
            $this->validatorClass(),
            $this->originalMessage,
            $this->getCode(),
            $this->getPrevious()
        );
    }

    /**
     * Return true if the exception was manipulated after instantiation (add
     * failures, rules etc).
     * This is needed due to some limitation in php exception in all final
     * base methods.
     *
     * @return bool
     */
    public function wasManipulated() : bool
    {
        return $this->wasManipulated;
    }

    /**
     * Parse a readable message by the
     * @param string $message
     * @param array $failures
     * @return string
     */
    protected function generateMessage(string $message, array $failures=[]) : string
    {
        if (!$failures) {
            return $message ?: 'Validation errors occurred.';
        }
        $message = $message ?: 'Validation errors: ';
        $formatted = [];
        foreach ($failures as $key=>$rules) {

            $ruleLine = [];
            foreach ($rules as $constraint=>$params) {
                $rLine = $constraint;
                $paramString = '';
                if ($params) {
                    $paramString = ':' . implode(',', $params);
                }
                $ruleLine[] = "$rLine$paramString";
            }
            $line = "$key=>" . implode('|', $ruleLine);
            $formatted[] = $line;
        }
        return $message . ' ' . implode(" ", $formatted);
    }
}