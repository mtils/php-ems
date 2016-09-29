<?php


namespace Ems\Core\Support;


trait ProvidesNamedCallableChain
{
    /**
     * @var array
     **/
    protected $chain = [];

    /**
     * @var array
     **/
    protected $casters = [];

    /**
     * {@inheritdoc}
     *
     * @return array
     **/
    public function getChain()
    {
        return $this->chain;
    }

    /**
     * {@inheritdoc}
     *
     * @param string|array $chain
     * @return self (same instance)
     **/
    public function setChain($chain)
    {
        $this->chain = is_string($chain) ? explode('|', $chain) : $chain;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param string|array $callable
     * @return self (New instance)
     **/
    public function with($callable)
    {

        $newChain = func_num_args() > 1 ? func_get_args() : $callable;

        if (is_string($newChain)) {
            $newChain = explode('|', $newChain);
        }

        $thisChain = $this->chain;

        foreach ($newChain as $name) {

            list($operator, $key) = $this->splitExpression($name);

            if ($operator != '!' && !in_array($key, $thisChain)) {
                $thisChain[] = $key;
                continue;
            }

            if (($idx = array_search($key, $thisChain)) !== false) {
                unset($thisChain[$idx]);
                $thisChain = array_values($thisChain);
            }
        }

        $newInstance = new static($this->casters);
        $newInstance->setChain($thisChain);

        return $newInstance;

    }

    /**
     * {@inheritdoc}
     *
     * @param string $name
     * @param callable $callable
     * @return self (same instance)
     **/
    public function add($name, callable $callable)
    {
        $this->casters[$name] = $callable;
        return $this;
    }

    /**
     * Cut the ! from the string
     *
     * @param string $name
     * @return array
     **/
    protected function splitExpression($name)
    {
        if (strpos($name, '!') === 0) {
            return ['!', ltrim($name, '!')];
        }

        return ['+', $name];
    }
}
