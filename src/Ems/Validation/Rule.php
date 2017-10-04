<?php


namespace Ems\Validation;

use ArrayIterator;
use Ems\Contracts\Validation\Rule as RuleContract;
use Ems\Core\Exceptions\KeyNotFoundException;
use Ems\Core\Support\StringableTrait;
use Ems\Core\Helper;


class Rule implements RuleContract
{
    use StringableTrait;
    use RuleParseMethods;

    /**
     * @var array
     **/
    protected $definitions = [];

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

        $definitions = $this->explodeRules($definition);

        foreach ($definitions as $index=>$definition) {

            list($name, $parameters) = is_array($definition) ?
                                       [$index, $definition] :
                                       $this->ruleAndParameters($definition);
            $this->definitions[Helper::snake_case($name)] = $parameters;

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
        $this->definitions = [];
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

        $name = Helper::snake_case($name);

        if (!$this->__isset($name)) {
            throw new KeyNotFoundException("No definition with key $name");
        }

        $count = count($this->definitions[$name]);

        // For easier access return just null if no parameters were set
        if ($count == 0) {
            return null;
        }

        // For easier access return just the first parameter
        if ($count == 1) {
            return $this->definitions[$name][0];
        }

        return $this->definitions[$name];
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
        $name = Helper::snake_case($name);
        $this->definitions[$name] = (array)$parameters;
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
        $name = Helper::snake_case($name);
        return isset($this->definitions[$name]);
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
        $name = Helper::snake_case($name);
        unset($this->definitions[$name]);
    }

    /**
     * @return int
     **/
    public function count()
    {
        return count($this->definitions);
    }

    /**
     * @return ArrayIterator
     **/
    public function getIterator()
    {
        return new ArrayIterator($this->definitions);
    }

    /**
     * Returns a rendered version of the definitions.
     *
     * @return string
     **/
    protected function renderString()
    {
        $definitions = [];
        foreach ($this->definitions as $name=>$definition) {
            $delimiter = $definition ? ':' : '';
            $definitions[] = $name . $delimiter . implode(',', $definition);
        }
        return implode('|', $definitions);
    }
}
