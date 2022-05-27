<?php

namespace Ems\Validation;

use Closure;
use Ems\Contracts\Core\Containers\ByTypeContainer;
use Ems\Contracts\Core\Exceptions\TypeException;
use Ems\Contracts\Core\Subscribable;
use Ems\Contracts\Core\SupportsCustomFactory;
use Ems\Contracts\Validation\Validator;
use Ems\Contracts\Validation\ValidatorFactory as ValidatorFactoryContract;
use Ems\Core\Checker;
use Ems\Core\Exceptions\UnsupportedParameterException;
use Ems\Core\Patterns\SubscribableTrait;
use Ems\Core\Support\CustomFactorySupport;
use Ems\Validation\Validator as ValidatorObject;
use OutOfBoundsException;
use ReflectionException;

use function get_class;
use function is_callable;
use function is_object;
use function is_string;
use function spl_object_hash;

class ValidatorFactory implements ValidatorFactoryContract, SupportsCustomFactory, Subscribable
{
    use CustomFactorySupport {
        createWithoutFactory as traitCreateWithoutFactory;
    }
    use SubscribableTrait {
        on as traitOn;
    }

    /**
     * @var ByTypeContainer
     */
    protected $factories;

    /**
     * @var callable
     */
    protected $createFactory;

    /**
     * @var Validator|null
     */
    protected $lastPublishedValidator;

    public function __construct(callable $factory = null)
    {
        $this->factories = new ByTypeContainer();
        if ($factory) {
            $this->createObjectsBy($factory);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @param array $rules
     * @param string $ormClass
     * @return Validator
     */
    public function create(array $rules, string $ormClass = ''): Validator
    {
        $factory = $this->getCreateFactory();
        if (!$ormClass) {
            return $factory($rules, $ormClass);
        }
        $validator = $factory($rules, $ormClass);
        $this->publish($ormClass, $validator);
        return $validator;
    }

    /**
     * {@inheritDoc}
     *
     * @param string $ormClass
     * @return Validator
     * @throws ReflectionException
     */
    public function get(string $ormClass): Validator
    {
        $validator = $this->validator($ormClass);
        $this->publish($ormClass, $validator);
        return $validator;
    }

    /**
     * {@inheritDoc}
     *
     * @param array $rules
     * @param array $input
     * @param object|null $ormObject
     * @param array $formats
     *
     * @return array
     */
    public function validate(array $rules, array $input, $ormObject = null, array $formats = []): array
    {
        $ormClass = is_object($ormObject) ? get_class($ormObject) : '';
        $validator = $this->create($rules, $ormClass);
        return $validator->validate($input, $ormObject, $formats);
    }

    /**
     * Register a validator for $ormClass.
     *
     * @param string $ormClass
     * @param string|callable $validatorClassOrFactory
     * @return void
     */
    public function register(string $ormClass, $validatorClassOrFactory)
    {
        $this->factories[$ormClass] = $this->checkAndReturn($validatorClassOrFactory);
    }

    /**
     * Get the factory that is used inside create.
     *
     * @return callable
     */
    public function getCreateFactory() : callable
    {
        if (!$this->createFactory) {
            $this->createFactory = $this->makeCreateFactory();
        }
        return $this->createFactory;
    }

    /**
     * Set the factory that is used in create.
     *
     * @param callable $factory
     * @return $this
     */
    public function setCreateFactory(callable $factory) : ValidatorFactory
    {
        $this->createFactory = $factory;
        return $this;
    }

    /**
     * Register a listener that gets informed if a validator for ormClass $event
     * is returned (by create or get).
     *
     * @param string $event The orm class
     * @param callable $listener
     *
     * @return ValidatorFactory
     */
    public function on($event, callable $listener) : ValidatorFactory
    {
        $this->traitOn($event, $listener);
        return $this;
    }

    /**
     * Use this method to forward resolving events from your container so that
     * the listeners will also be called.
     *
     * @param Validator $validator
     * @return void
     */
    public function forwardValidatorEvent(Validator $validator)
    {

        if (!$ormClass = $validator->ormClass()) {
            return;
        }
        $this->publish($ormClass, $validator);
        return;
        if (!$this->lastPublishedValidator) {
            $this->publish($ormClass, $validator);
            return;
        }

        if (spl_object_hash($validator) != spl_object_hash($this->lastPublishedValidator)) {
            $this->publish($ormClass, $validator);
        }
    }

    /**
     * Make the default handler for creating fresh validators in self::create()
     *
     * @return Closure
     */
    protected function makeCreateFactory() : Closure
    {
        return function (array $rules, string $ormClass='') {
            return $this->createObject(ValidatorObject::class, [
                'rules'     => $rules,
                'ormClass'  => $ormClass,
                $this->createObject(CheckerBaseValidator::class)
            ]);
        };
    }

    /**
     * @param string $ormClass
     * @return Validator
     * @throws ReflectionException
     */
    protected function validator(string $ormClass) : Validator
    {
        if (!$factoryOrClass = $this->factories->forInstanceOf($ormClass)) {
            throw new OutOfBoundsException("No handler registered for class '$ormClass'");
        }

        $validator = is_callable($factoryOrClass) ? $factoryOrClass($ormClass) : $this->createObject($factoryOrClass);

        if ($validator instanceof Validator) {
            return $validator;
        }

        if (is_string($factoryOrClass)) {
            throw new TypeException("The registered class or binding $factoryOrClass must implement " . Validator::class);
        }

        $type = is_object($factoryOrClass) ? get_class($factoryOrClass) : 'callable';

        throw new TypeException("The registered factory of type '$type' did not return a " . Validator::class);

    }

    /**
     * Check the factory before adding it.
     *
     * @param $validatorClassOrFactory
     * @return string|callable
     * @throws UnsupportedParameterException
     */
    protected function checkAndReturn($validatorClassOrFactory)
    {
        if ($validatorClassOrFactory instanceof Validator) {
            throw new UnsupportedParameterException("It is not allowed to register validators directly to avoid loading masses of classes on boot");
        }

        // Class or callable. Avoid checking for class existence to not load
        // files for nothing
        if (!is_string($validatorClassOrFactory) && !is_callable($validatorClassOrFactory)) {
            throw new UnsupportedParameterException('Factory has to be a class name or a callable.');
        }

        return $validatorClassOrFactory;
    }

    /**
     * Reimplemented over trait to accept class names.
     *
     * @param string $event
     * @return void
     */
    protected function checkEvent($event)
    {
        // Accept everything
    }

    /**
     * Overwritten in case of missing factory.
     *
     * @param string $abstract
     * @param array $parameters
     * @return CheckerBaseValidator|object
     * @throws ReflectionException
     */
    protected function createWithoutFactory($abstract, array $parameters = [])
    {
        if ($abstract === CheckerBaseValidator::class) {
            return new CheckerBaseValidator($this->createObject(Checker::class));
        }
        return $this->traitCreateWithoutFactory($abstract, $parameters);
    }

    /**
     * Publish the validator by subscribable trait.
     *
     * @param string $ormClass
     * @param Validator $validator
     * @return void
     */
    protected function publish(string $ormClass, Validator $validator)
    {
        if ($this->isLastPublishedValidator($validator)) {
            return;
        }
        $this->lastPublishedValidator = $validator;
        $this->callOnListeners($ormClass, [$validator]);
    }

    /**
     * Check if the passed validator is the last published. This is needed to
     * avoid double events for validators.
     *
     * @param Validator $validator
     * @return bool
     */
    protected function isLastPublishedValidator(Validator $validator) : bool
    {
        if (!$this->lastPublishedValidator) {
            return false;
        }
        return spl_object_hash($validator) == spl_object_hash($this->lastPublishedValidator);
    }
}
