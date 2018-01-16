<?php
/**
 *  * Created by mtils on 13.01.18 at 08:19.
 **/

namespace Ems\Model;


use Ems\Contracts\Expression\ConditionGroup as ConditionGroupContract;
use Ems\Contracts\Model\Result;
use Ems\Contracts\Model\SearchEngine;
use Ems\Expression\ConditionGroup;

class NullSearchEngine implements SearchEngine
{
    /**
     * {@inheritdoc}
     *
     * @return ConditionGroupContract
     */
    public function newQuery()
    {
        return new ConditionGroup();
    }

    /**
     * {@inheritdoc}
     *
     * @param ConditionGroupContract $conditions
     * @param array                  $sorting (optional)
     * @param string[]               $keys (optional)
     *
     * @return Result
     */
    public function search(ConditionGroupContract $conditions, array $sorting = [], $keys = [])
    {
        return new GenericPaginatableResult([],[], $this);
    }

}