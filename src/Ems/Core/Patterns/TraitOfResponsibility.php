<?php

namespace Ems\Core\Patterns;

use InvalidArgumentException;
use ReflectionClass;
use Ems\Core\Exceptions\HandlerNotFoundException;


trait TraitOfResponsibility
{
    /**
     * @var array
     **/
    protected $candidates = [];

    /**
     * @var array
     **/
    protected $candidateIds = [];

    /**
     * @var string
     **/
    protected $detectedType;

    /**
     * Add a candidate to the chain.
     *
     * @param object $candidate
     *
     * @return self
     **/
    public function add($candidate)
    {
        $hash = $this->objectHash($candidate);

        if (isset($this->candidateIds[$hash])) {
            return $this;
        }

        $function = $this->shouldCallReversed() ? 'array_unshift' : 'array_push';

        $function($this->candidates, $this->checkAndReturn($candidate));

        $this->candidateIds[$hash] = true;

        return $this;
    }

    /**
     * Add a candidate the none of this class was added.
     *
     * @param object $canditate
     *
     * @return self
     **/
    public function addIfNoneOfClass($candidate)
    {
        if ($this->containsClass($candidate)) {
            return $this;
        }

        return $this->add($candidate);
    }

    /**
     * Remove a candidate from the chain.
     *
     * @param object $candidate
     *
     * @return self
     **/
    public function remove($candidate)
    {
        $hash = $this->objectHash($candidate);

        $this->candidates = array_filter($this->candidates, function ($known) use ($hash) {
            return $this->objectHash($known) != $hash;
        });

        if (isset($this->candidateIds[$hash])) {
            unset($this->candidateIds[$hash]);
        }

        return $this;
    }

    /**
     * Find a handler by a method returning true. This is
     * for chains which have separate "supports($parameter)" or
     * "can($parameter) method.
     *
     * @param string $method
     *
     * @return self|null
     **/
    protected function findReturningTrue($method)
    {
        $args = func_get_args();

        $method = array_shift($args);

        foreach ($this->candidates as $candidate) {
            if (call_user_func_array([$candidate, $method], $args)) {
                return $candidate;
            }
        }
    }

    /**
     * Same as self::findReturningTrue but throw an exception
     * if none found.
     *
     * @param string $method
     *
     * @return self
     *
     * @throws Ems\Core\Exceptions\HandlerNotFoundException
     **/
    protected function findReturningTrueOrFail($method)
    {
        if (!$candidate = call_user_func_array([$this, 'findReturningTrue'], func_get_args())) {
            throw new HandlerNotFoundException("No matching handler by $method found in ".get_class($this));
        }

        return $candidate;
    }

    /**
     * Call $method on every extension. Return the first not null
     * result. this is 
     *
     * @param string $method
     *
     * @return self|null
     **/
    protected function firstNotNullResult($method)
    {
        $args = func_get_args();

        $method = array_shift($args);

        foreach ($this->candidates as $candidate) {

            $result = call_user_func_array([$candidate, $method], $args);

            if ($result !== null) {
                return $result;
            }

        }
    }

    /**
     * Call $method on every extension. Return the first not null
     * result. Throw exception if no result was returned.
     *
     * @param string $method
     *
     * @return self|null
     **/
    protected function firstNotNullResultOrFail($method)
    {
        $result = call_user_func_array([$this, 'firstNotNullResult'], func_get_args());
        if ($result === null) {
            throw new HandlerNotFoundException("No handler returned a value by $method");
        }
        return $result;
    }

    /**
     * Detected the forced type to add to this chain.
     *
     * @return string
     **/
    protected function getCandidateType()
    {
        if (!$this->detectedType) {
            $this->detectedType = $this->detectType();
        }

        return $this->detectedType;
    }

    /**
     * Find out if the last added extension should be called
     * at first or at end.
     *
     * @return bool
     **/
    protected function shouldCallReversed()
    {
        return property_exists($this, 'callReversed') ? $this->callReversed : true;
    }

    /**
     * Check the type of the extension and return it.
     *
     * @param mixed $candidate
     *
     * @return mixed
     **/
    protected function checkAndReturn($candidate)
    {
        if (!is_object($candidate)) {
            throw new InvalidArgumentException('Only objects are supported not '.gettype($candidate));
        }

        $type = $this->getCandidateType();

        if ($candidate instanceof $type) {
            return $candidate;
        }

        $thisClass = get_class($this);

        throw new InvalidArgumentException("You can only add $type objects to this object. Or define another class via an allow property in $thisClass");
    }

    /**
     * For Countable interface.
     *
     * @return int
     **/
    public function count()
    {
        return count($this->candidates);
    }

    /**
     * Return if the passed object was added to this Chain.
     *
     * @param object
     *
     * @return bool
     **/
    public function contains($candidate)
    {
        return isset($this->candidateIds[$this->objectHash($candidate)]);
    }

    /**
     * Return if a object of $class was added to this chain.
     *
     * @param string|object $class
     *
     * @return bool
     **/
    public function containsClass($class)
    {
        $class = is_object($class) ? get_class($class) : $class;
        foreach ($this->candidates as $candidate) {
            if ($candidate instanceof $class) {
                return true;
            }
        }

        return false;
    }

    /**
     * Try to guess the type of its candidates. First look for
     * an property "allow". If this is not set search the interfaces,
     * finally just return the class of this object.
     *
     * @return string
     **/
    protected function detectType()
    {
        if (property_exists($this, 'allow')) {
            return $this->allow;
        }

        $interfaces = (new ReflectionClass($this))->getInterfaceNames();

        if ($interfaces) {
            return $interfaces[0];
        }

        return get_class($this);
    }

    /**
     * Return an id to intentify one instance of an object.
     *
     * @param object
     *
     * @return string
     **/
    protected function objectHash($object)
    {
        return is_object($object) ? spl_object_hash($object) : var_export($object, true);
    }
}
