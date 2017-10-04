<?php

namespace Ems\Validation;

use Ems\Contracts\Validation\ValidatorFactory as ValidatorFactoryContract;
use Ems\Contracts\Validation\Validator as ValidatorContract;
use Ems\Contracts\Core\AppliesToResource;
use Ems\Contracts\Core\SupportsCustomFactory;
use Ems\Core\Exceptions\HandlerNotFoundException;
use Ems\Core\Patterns\TraitOfResponsibility;
use Ems\Core\Support\CustomFactorySupport;
use InvalidArgumentException;

class ValidatorFactory implements ValidatorFactoryContract, SupportsCustomFactory
{
    use TraitOfResponsibility;
    use CustomFactorySupport;
    use ConfiguresValidator;

    /**
     * @var array
     **/
    protected $resourceFactories = [];

    /**
     * {@inheritdoc}
     *
     * @param array             $rules
     * @param AppliesToResource $resource (optional
     *
     * @return Validator
     **/
    public function make(array $rules, AppliesToResource $resource=null)
    {

        if (!$resource) {
            $validator = $this->firstNotNullResultOrFail('make', $rules, $resource);
            return $this->configureAndReturn($validator, $rules, $resource);
        }

        $resourceName = $resource->resourceName();

        if (!isset($this->resourceFactories[$resourceName])) {
            return $this->firstNotNullResultOrFail('make', $rules, $resource);
        }

        $validator = call_user_func($this->resourceFactories[$resourceName]);

        return $this->configureAndReturn($validator, $rules, $resource);

    }

    /**
     * Return if the factory has a validator for $resource
     *
     * @param string|AppliesToResource $resource
     *
     * @return bool
     **/
    public function hasForResource($resource)
    {
        try {
            return (bool)$this->getForResource($resource);
        } catch (HandlerNotFoundException $e) {
            return false;
        }
    }

    /**
     * Get the validator factory setted for a resource
     *
     * @param string|AppliesToResource $resource
     *
     * @return callable
     **/
    public function getForResource($resource)
    {
        $resourceName = $this->resourceName($resource);

        if (isset($this->resourceFactories[$resourceName])) {
            return $this->resourceFactories[$resourceName];
        }

        throw new HandlerNotFoundException("No handler found for resource '$resourceName'");

    }

    /**
     * Set a custom validator for one resource name.
     * The assigned validator is used for the resource instead of all others in
     * the chain.
     *
     * @param string|AppliesToResource          $resource
     * @param string|ValidatorContract|callable $classOrCallableOrInstance
     *
     * @return self
     **/
    public function setForResource($resource, $classOrCallableOrInstance)
    {

        // short this awful long name...
        $factory = $classOrCallableOrInstance;

        $resourceName = $this->resourceName($resource);

        if ($factory instanceof ValidatorContract) {
            $this->resourceFactories[$resourceName] = function () use ($factory) {
                return clone $factory; // Always return a fresh instance
            };
            return $this;
        }

        if (is_callable($factory)) {
            $this->resourceFactories[$resourceName] = $factory;
            return $this;
        }

        if (is_string($factory)) {
            $this->resourceFactories[$resourceName] = function () use ($factory) {
                return $this->createObject($factory);
            };
            return $this;
        }

        throw new InvalidArgumentException('You can pass a classname, callable or Validator');
    }

    /**
     * Remove the validator factory setted for $resource
     *
     * @param string|AppliesToResource $resource
     *
     * @return self
     **/
    public function unsetForResource($resource)
    {
        $resourceName = $this->resourceName($resource);

        if (!isset($this->resourceFactories[$resourceName])) {
            throw new HandlerNotFoundException("No handler found for resource '$resourceName'");
        }

        unset($this->resourceFactories[$resourceName]);

        return $this;

    }

    /**
     * @param string|AppliesToResource $resource
     *
     * @return string
     **/
    protected function resourceName($resource)
    {
        return $resource instanceof AppliesToResource ? $resource->resourceName() : $resource;
    }
}
