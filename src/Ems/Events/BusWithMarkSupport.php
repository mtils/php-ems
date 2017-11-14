<?php

namespace Ems\Events;


use Ems\Contracts\Events\Bus as BusContract;
use Ems\Core\Exceptions\UnsupportedParameterException;
use Ems\Core\Patterns\HookableTrait;
use Ems\Core\Patterns\SubscribableTrait;
use Ems\Core\Helper;
use Ems\Core\Lambda;
use LogicException;

/**
 * 
 **/
class BusWithMarkSupport extends Bus
{
    /**
     * @var array
     **/
    protected $marks = [];

    /**
     * @var array
     **/
    protected $markFilters = [];

    /**
     * @param Bus $bus
     **/
    public function __construct(Bus $root, array $marks=[], array $markFilters=[])
    {
        parent::__construct($root->usedMatchingListener());
        $this->root = $root;
        $this->marks = $this->checkAndReturnMarks($marks);
        $this->markFilters = $markFilters;
    }

    /**
     * {@inheritdoc}
     *
     * @param string|object $event
     * @param callable      $listener
     *
     * @return self
     **/
    public function onBefore($event, callable $listener)
    {
        if ($this->markFilters) {
            $listener = $this->wrap($listener, '*', $this->markFilters);
        }

        $this->root->onBefore($event, $listener);

        return $this;

    }

     /**
     * {@inheritdoc}
     *
     * @param string   $event
     * @param callable $listener
     *
     * @return self
     **/
    public function on($event, callable $listener)
    {
        if ($this->markFilters) {
            $listener = $this->wrap($listener, '*', $this->markFilters);
        }

        $this->root->on($event, $listener);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param string|object $event
     * @param callable      $listener
     *
     * @return self
     **/
    public function onAfter($event, callable $listener)
    {
        if ($this->markFilters) {
            $listener = $this->wrap($listener, '*', $this->markFilters);
        }

        $this->root->onAfter($event, $listener);

        return $this;

    }

    /**
     * {@inheritdoc}
     *
     * @param string|array $mark
     * @param mixed        $value (optional)
     *
     * @return self
     *
     * @example self::mark('no-broadcast')->fire('users.updated', [])
     **/
     public function mark($mark, $value=null)
     {
        $marks = $this->matchingListener->markToArray($mark, $value, $this->knownMarks);
        $newMarks = array_merge($this->marks, $marks);
        return new static($this->root, $newMarks, $this->markFilters);
     }

    /**
     * {@inheritdoc}
     *
     * @param string|array $mark
     * @param mixed        $value (optional)
     *
     * @return self
     *
     * @example self::when('!no-broadcast')->on('users.updated', f())
     **/
     public function when($mark, $value=null)
     {
        $markFilter = $this->matchingListener->markToArray($mark, $value, $this->knownMarks);
        $newMarkFilter = array_merge($this->markFilters, $markFilter);
        return new static($this->root, $this->marks, $newMarkFilter);
     }

    /**
     * Calls a listener.
     *
     * @param callable $listener
     * @param string   $event
     * @param array    $args
     *
     * @return mixed
     **/
    protected function callListener(callable $listener, $event, array $args)
    {

        if (!$this->marks) {
            return Lambda::callFast($listener, $args);
        }

        if ($listener instanceof MatchingListener) {
            return $listener->callWithMarks($args, $this->marks);
        }

        return Lambda::callFast($listener, $args);

    }

    /**
     * Checks if in the call of mark() ... some values were explicit casted to
     * to false. This would lead to errors, because a when('!mark') means "The
     * mark is not setted", not "the mark is set to false".
     * Combine this with mark('!mark', true) and when('!mark', false) and you
     * know what I mean...
     *
     * @param array $marks
     *
     * @return array
     **/
    protected function checkAndReturnMarks(array $marks)
    {
        foreach ($marks as $mark=>$value) {
            if ($value === false) {
                throw new LogicException("Marking a mark as explicit false like you did on $mark is not allowed.");
            }
        }

        return $marks;
    }

}
