<?php

namespace Ems\Foundation;

use Ems\Contracts\Core\AppliesToResource;
use Ems\Contracts\Foundation\InputProcessor as InputProcessorContract;
use Ems\Contracts\Foundation\InputNormalizer as InputNormalizerContract;
use Ems\Contracts\Validation\ValidatorFactory;
use Ems\Contracts\Validation\Validator;
use Ems\Core\Patterns\HookableTrait;
use Ems\Core\Exceptions\MisConfiguredException;
use Ems\Core\Exceptions\UnsupportedParameterException;
use Ems\Contracts\Core\Type;
use UnexpectedValueException;

use function get_class;
use function is_object;

class InputNormalizer implements InputNormalizerContract
{
    use HookableTrait;

    /**
     * @var ValidatorFactory
     **/
    protected $validatorFactory;

    /**
     * @var InputProcessorContract
     **/
    protected $adjuster;

    /**
     * @var Validator
     **/
    protected $validator;

    /**
     * @var array
     **/
    protected $validationRules = [];

    /**
     * @var InputProcessorContract
     **/
    protected $caster;

    /**
     * @var bool
     **/
    protected $shouldAdjust = true;

    /**
     * @var bool
     **/
    protected $shouldValidate = true;

    /**
     * @var bool
     **/
    protected $shouldCast = true;


    public function __construct(ValidatorFactory $validatorFactory,
                                InputProcessorContract $adjuster=null,
                                InputProcessorContract $caster=null)
    {
        $this->validatorFactory = $validatorFactory;
        $this->adjuster = $adjuster ?: new InputProcessor;
        $this->caster = $caster ?: new InputProcessor;
    }

    /**
     * {@inheritdoc}
     *
     * @param string|bool|InputProcessorContract $chain (optional)
     *
     * @return self
     **/
    public function adjust($chain=null)
    {

        if ($chain === null || $chain === true) {
            $this->shouldAdjust = true;
            return $this;
        }

        if ($chain === false) {
            $this->shouldAdjust = false;
            return $this;
        }

        if ($chain instanceof InputProcessorContract) {
            $this->adjuster = $chain;
            $this->shouldAdjust = true;
            return $this;
        }

        $chain = func_num_args() > 1 ? func_get_args() : $chain;
        $this->adjuster = $this->adjuster->with($chain);
        $this->shouldAdjust = true;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param array|bool|\Ems\Contracts\Validation\Validator $constraint (optional)
     *
     * @return self
     **/
    public function validate($constraint=null)
    {
        if ($constraint === null || $constraint === true) {
            $this->shouldValidate = true;
            return $this;
        }

        if ($constraint === false) {
            $this->shouldValidate = false;
            return $this;
        }

        if ($constraint instanceof Validator) {
            $this->validator = $constraint;
            $this->shouldValidate = true;
            return $this;
        }

        if (is_array($constraint)) {
            $this->validationRules = $constraint;
            $this->shouldValidate = true;
            return $this;
        }

        throw new UnsupportedParameterException("Unsupported parameter type: " . Type::of($constraint));

    }

    /**
     * {@inheritdoc}
     *
     * @param string|bool|InputProcessorContract $chain (optional)
     *
     * @return self
     **/
    public function cast($chain=null)
    {

        if ($chain === null || $chain === true) {
            $this->shouldCast = true;
            return $this;
        }

        if ($chain === false) {
            $this->shouldCast = false;
            return $this;
        }

        if ($chain instanceof InputProcessorContract) {
            $this->caster = $chain;
            $this->shouldCast = true;
            return $this;
        }

        $chain = func_num_args() > 1 ? func_get_args() : $chain;
        $this->caster = $this->caster->with($chain);
        $this->shouldCast = true;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param array                    $input
     * @param string|AppliesToResource $resource (optional)
     * @param string                   $locale (optional)
     *
     * @return array
     **/
    public function normalize(array $input, AppliesToResource $resource=null, $locale=null)
    {

        if ($this->shouldAdjust) {
            $input = $this->processListeners('adjust', 'before', $input, $resource, $locale);
            $input = $this->adjuster->process($input, $resource, $locale);
            $input = $this->processListeners('adjust', 'after', $input, $resource, $locale);
        }

        $this->validateIfDesired($input, $resource);

        if ($this->shouldCast) {
            $input = $this->processListeners('cast', 'before', $input, $resource, $locale);
            $input = $this->caster->process($input, $resource, $locale);
            $input = $this->processListeners('cast', 'after', $input, $resource, $locale);
        }

        return $input;
    }

    /**
     * Validate if validate is on.
     *
     * @array $input
     *
     * @return bool
     **/
    protected function validateIfDesired(array $input, $ormObject=null, array $formats=[])
    {

        if (!$this->shouldValidate) {
            return true;
        }

        $ormClass = is_object($ormObject) ? get_class($ormObject) : '';

        $validator = $this->validator ?: $this->validatorFactory->create($this->validationRules, $ormClass);

        if (!$validator) {
            throw new MisConfiguredException("Cannot validate without a validator or rules");
        }

        $this->callBeforeListeners('validate', [$input, $ormObject, $formats]);
        $validator->validate($input, $ormObject, $formats);
        $this->callAfterListeners('validate', [$input, $ormObject, $formats]);

        return true;

    }

    /**
     * {@inheritdoc}
     *
     * @return array
     **/
    public function methodHooks()
    {
        return ['adjust', 'validate', 'cast'];
    }

    /**
     * Process the input by its listeners. Instead using the default behaviour
     * callBeforeListeners/callAfterListeners we need the output here.
     *
     * @param string            $hook
     * @param string            $position
     * @param array             $input
     * @param AppliesToResource $resource (optional)
     * @param string            $locale   (optional)
     *
     * @return array
     **/
    protected function processListeners($hook, $position, array $input, AppliesToResource $resource=null, $locale=null)
    {

        if (!$listeners = $this->getListeners($hook, $position)) {
            return $input;
        }

        foreach ($listeners as $listener) {
            $input = $listener($input, $resource, $locale);
            if (!is_array($input)) {
                throw new UnexpectedValueException('Every listener of InputNormalizer has to return the input not ' . Type::of($input));
            }
        }

        return $input;
    }
}
