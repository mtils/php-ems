<?php

namespace Ems\Model;

use ArrayIterator;
use Ems\Contracts\Expression\Prepared;
use Ems\Contracts\Model\Database\Connection;
use Ems\Contracts\Model\StorageQuery as StorageQueryContract;
use Ems\Contracts\Core\Storage;
use Ems\Core\ArrayWithState;
use Ems\Core\Support\TrackedArrayDataTrait;
use Ems\Core\Exceptions\UnConfiguredException;
use Ems\Expression\ConditionGroup;
use Ems\Model\Database\SQLNameNotFoundException;
use Ems\Model\ResultTrait;


/**
 * The StorageQuery is a simple proxy for an easy syntax to allow queries in
 * Storages.
 **/
class StorageQuery extends ConditionGroup implements StorageQueryContract
{
    use ResultTrait;

    /**
     * @var callable
     **/
    protected $selector;

    /**
     * @var callable
     **/
    protected $purger;

    /**
     * @param callable $selector
     * @param callable $purger
     * @param array    $conditions (optional)
     * @param string   $operator (default:and)
     **/
    public function __construct(callable $selector, callable $purger, $conditions=[], $operator='and')
    {
        $this->selector = $selector;
        $this->purger = $purger;
        parent::__construct($conditions, $operator);
    }

    /**
     * {@inheritdoc}
     *
     * No lazy loading stuff, makes it very complicated...
     *
     * @return \Iterator
     **/
    public function getIterator()
    {
        $results = call_user_func($this->selector, $this);
        return new ArrayIterator($results);
    }

    /**
     * {@inheritdoc}
     *
     * No lazy loading stuff, makes it very complicated...
     *
     * @return bool (if successfull)
     **/
    public function purge()
    {
        return call_user_func($this->purger, $this);
    }

    /**
     * Create a fork of the condition group
     *
     * @param array $conditions
     * @param string $boolean
     *
     * @return self
     **/
    protected function fork(array $conditions, $boolean)
    {
        return new static($this->selector, $this->purger, $conditions, $boolean);
    }
}
