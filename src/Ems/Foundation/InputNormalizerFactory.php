<?php

namespace Ems\Foundation;

use Ems\Contracts\Core\AppliesToResource;
use Ems\Contracts\Foundation\InputNormalizer as InputNormalizerContract;
use Ems\Contracts\Foundation\InputNormalizerFactory as NormalizerFactoryContract;
use Ems\Contracts\Foundation\InputProcessor as InputProcessorContract;
use Ems\Contracts\Validation\ValidatorFactory;
use Ems\Core\Patterns\ExtendableTrait;
use Ems\Core\Patterns\HookableTrait;
use InvalidArgumentException;


class InputNormalizerFactory implements NormalizerFactoryContract
{

    use ExtendableTrait;
    use HookableTrait;

    /**
     * @var InputProcessorContract
     **/
    protected $adjuster;

    /**
     * @var ValidatorFactory
     **/
    protected $validatorFactory;

    /**
     * @var InputProcessorContract
     **/
    protected $caster;

    /**
     * @var callable
     **/
    protected $normalizerCreator;

    /**
     * @var InputNormalizerContract
     **/
    protected $normalizerPrototype;

    /**
     * @var array
     **/
    protected $priorityCache = [];

    public function __construct(ValidatorFactory $validatorFactory,
                                InputProcessorContract $adjuster=null,
                                InputProcessorContract $caster=null
                                )
    {
        $this->adjuster = $adjuster ?: new InputProcessor;
        $this->validatorFactory = $validatorFactory;
        $this->caster = $caster ?: new InputProcessor;
        $this->createNormalizerBy(function ($factory, $adjuster, $caster) {
            return new InputNormalizer($factory, $adjuster, $caster);
        });
    }

    /**
     * {@inheritdoc}
     *
     * @param string                   $inputType
     * @param string|AppliesToResource $resource (optional)
     * @param string                   $locale (optional)
     *
     * @return array
     **/
    public function normalizer($inputType, $resource=null, $locale=null)
    {

        $extensions = $this->collectExtensions($inputType);

        $normalizer = $this->createNormalizer();

        $this->copyListeners($normalizer);

        foreach ($extensions as $extension) {
            call_user_func($extension, $normalizer, $inputType, $resource, $locale);
        }

        return $normalizer;
    }

    /**
     * {@inheritdoc}
     *
     * @return InputProcessor
     **/
    public function adjuster()
    {
        return $this->adjuster;
    }

    /**
     * {@inheritdoc}
     *
     * @return ValidatorFactory
     **/
    public function validatorFactory()
    {
        return $this->validatorFactory;
    }

    /**
     * {@inheritdoc}
     *
     * @return InputProcessor
     **/
    public function caster()
    {
        return $this->caster;
    }

    /**
     * Return just the method hooks of the normalizers, because we just
     * forward them to the normalizers.
     *
     * @return array
     **/
    public function methodHooks()
    {
        return $this->normalizerPrototype()->methodHooks();
    }

    /**
     * Assign a custom callable to create the InputNormalizer instances. The
     * validatorFactory, adjuster and caster are passed to the callable.
     *
     * CAUTION: The normalizers are creating forks of their adjusters and casters.
     * You have to pass them or the whole thing will stop to work.
     *
     * @param callable $creator
     *
     * @return self
     **/
    public function createNormalizerBy(callable $creator)
    {
        $this->normalizerCreator = $creator;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * (Reimplemented to check for wrong segment counts on extend.)
     *
     * @param string   $name
     * @param callable $callable
     *
     * @return self
     **/
    public function extend($name, callable $callable)
    {
        $name = $name == '*' ? '*.*.*' : $name;
        $this->checkSegmentCount($name);
        $this->_extensions[$name] = $callable;

        return $this;
    }

    protected function createNormalizer()
    {
        $adjuster = $this->adjuster->with($this->adjuster->getChain());
        $caster = $this->adjuster->with($this->caster->getChain());
        return call_user_func($this->normalizerCreator, $this->validatorFactory, $adjuster, $caster);
    }

    protected function copyListeners(InputNormalizerContract $normalizer)
    {

        foreach ($this->beforeListeners as $event=>$listeners) {
            foreach ($listeners as $listener) {
                $normalizer->onBefore($event, $listener);
            }
        }

        foreach ($this->afterListeners as $event=>$listeners) {
            foreach ($listeners as $listener) {
                $normalizer->onAfter($event, $listener);
            }
        }
    }

    protected function normalizerPrototype()
    {
        if (!$this->normalizerPrototype) {
            $this->normalizerPrototype = $this->createNormalizer();
        }
        return $this->normalizerPrototype;
    }

    protected function collectExtensions($inputType)
    {
        $extensions = [];
        foreach ($this->createPriorityList($inputType) as $pattern) {
            if (isset($this->_extensions[$pattern])) {
                $extensions[] = $this->_extensions[$pattern];
            }
        }
        return $extensions;
    }

    protected function createPriorityList($inputType)
    {

        if (isset($this->priorityCache[$inputType])) {
            return $this->priorityCache[$inputType];
        }

        if (strpos($inputType, '*') !== false) {
            throw new InvalidArgumentException('* is not allowed in input types, only in extension registration');
        }

        $segments = explode('.', $inputType);

        $this->checkSegmentCount($segments);

        list($protocol, $client, $method) = $segments;

        $this->priorityCache[$inputType] = [
            '*.*.*',
            "$protocol.*.*",
            "*.$client.*",
            "*.*.$method",
            "$protocol.$client.*",
            "$protocol.*.$method",
            "*.$client.$method",
            "$protocol.$client.$method"
        ];

        return $this->priorityCache[$inputType];

    }

    protected function checkSegmentCount($segments)
    {
        $segments = is_array($segments) ? $segments : explode('.', $segments);

        if (count($segments) != 3) {
            throw new InvalidArgumentException('InputType and patterns has to always have a count of 3.');
        }
    }
}
