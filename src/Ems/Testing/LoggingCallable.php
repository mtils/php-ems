<?php

namespace Ems\Testing;

use Countable;

/**
 * The LoggingCallable allows to fast testing listener calls
 * It logs all calls and their args.
 **/
class LoggingCallable implements Countable
{
    /**
     * Here all calls are logged
     *
     * @var array
     **/
    protected $calls = [];

    /**
     * The fake callable
     *
     * @return void
     **/
    public function __invoke()
    {
        $this->calls[] = func_get_args();
    }

    /**
     * Returns the argument with idx from a call of this object.
     * If no callIndex is given, the last one is used
     *
     * @param int $idx The idx of the arg
     * @param int $callIndex (optional) The index of the call (0 is the first call)
     * @return mixed
     **/
    public function arg($idx, $callIndex=-1)
    {
        $args = $this->args($callIndex);
        return $args[$idx];
    }

    /**
     * Returns the arguments from a call of this object.
     * If no callIndex is given, the last one is used
     *
     * @param int $callIndex (optional) The index of the call (0 is the first call)
     * @return mixed
     **/
    public function args($callIndex=-1)
    {
        $callIndex = $callIndex == -1 ? count($this->calls) - 1 : $callIndex;
        return $this->calls[$callIndex];
    }

    /**
     * Returns how many times it was invoked
     *
     * @return int
     **/
    public function count()
    {
        return count($this->calls);
    }
}