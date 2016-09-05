<?php


namespace Ems\Core\Patterns;

use RuntimeException;
use InvalidArgumentException;
use ReflectionClass;
use Ems\Core\HandlerNotFoundException;

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
     * Add a candidate to the chain
     *
     * @param object $candidate
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
     * Remove a candidate from the chain
     *
     * @param object $candidate
     * @return self
     **/
    public function remove($candidate)
    {

        $hash = $this->objectHash($candidate);

        $this->candidates = array_filter($this->candidates, function($known) use ($hash) {
            return ($this->objectHash($known) != $hash);
        });

        if (isset($this->candidateIds[$hash])) {
            unset($this->candidateIds[$hash]);
        }

        return $this;

    }

    protected function findReturningTrue($method)
    {

        $args = func_get_args();

        $method = array_shift($args);

        if (!is_string($method)) {
            throw new InvalidArgumentException('method has to be a string not ' . gettype($method));
        }

        foreach ($this->candidates as $candidate) {
            if (call_user_func_array([$candidate, $method], $args)) {
                return $candidate;
            }
        }
    }

    protected function findReturningTrueOrFail($method)
    {
        if (!$candidate = call_user_func_array([$this, 'findReturningTrue'], func_get_args())) {
            throw new HandlerNotFoundException("No matching handler by $method found in " . get_class($this));
        }
        return $candidate;
    }

    protected function getCandidateType()
    {
        if (!$this->detectedType) {
            $this->detectedType = $this->detectType();
        }
        return $this->detectedType;
    }

    protected function shouldCallReversed()
    {
        return property_exists($this, 'callReversed') ? $this->callReversed : true;
    }

    protected function checkAndReturn($candidate)
    {

        if (!is_object($candidate)) {
            throw new InvalidArgumentException('Only objects are supported not ' . gettype($candidate));
        }

        $type = $this->getCandidateType();

        if ($candidate instanceof $type) {
            return $candidate;
        }

        $thisClass = get_class($this);

        throw new InvalidArgumentException("You can only add $type objects to this object. Or define another class via an allow property in $thisClass");


    }

    /**
     * For Countable interface
     *
     * @return int
     **/
    public function count()
    {
        return count($this->candidates);
    }

    /**
     * Return if the passed object was added to this Chain
     *
     * @param object
     * @return bool
     **/
    public function contains($candidate)
    {
        return isset($this->candidateIds[$this->objectHash($candidate)]);
    }

    /**
     * Return if a object of $class was added to this chain
     *
     * @param string|object $class
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
     * Return an id to intentify one instance of an object
     *
     * @param object
     * @return string
     **/
    protected function objectHash($object)
    {
        return spl_object_hash($object);
    }

}
