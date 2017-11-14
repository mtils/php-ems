<?php


namespace Ems\Expression;


use Ems\Contracts\Expression\ConditionGroup as ConditionGroupContract;


class ConditionGroup implements ConditionGroupContract
{
    use ConditionGroupTrait;

    /**
     * @param array  $conditions (optional)
     * @param string $operator (default='and')
     **/
    public function __construct(array $conditions=[], $operator='and')
    {

        $this->setOperator($operator);

        if (!$conditions) {
            return;
        }

        foreach ($conditions as $condition) {
            $this->add($condition);
        }
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
        return new static($conditions, $boolean);
    }

}
