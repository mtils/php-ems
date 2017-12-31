<?php
/**
 *  * Created by mtils on 31.12.17 at 11:43.
 **/

namespace Ems\Expression;

use Ems\Contracts\Expression\ConditionGroup as ConditionGroupContract;


class MatcherQuery extends ConditionGroup implements ConditionGroupContract
{
    /**
     * @var Matcher
     */
    protected $matcher;

    /**
     * MatcherQuery constructor.
     *
     * @param Matcher $matcher
     * @param array    $conditions (optional)
     * @param string   $operator (default:and)
     */
    public function __construct(Matcher $matcher, $conditions=[], $operator='and')
    {
        $this->matcher = $matcher;
        parent::__construct($conditions, $operator);
    }

    /**
     * Check if $value matches this query.
     * Normally you pass data to match, but it is also possible to just
     * match arbitrary expressions like 1 < 2.
     *
     * @param $value (optional)
     *
     * @return bool
     */
    public function matches($value=null)
    {
        return $this->matcher->matches($value, $this);
    }

    /**
     * {@inheritdoc}
     *
     * @param array $conditions
     * @param string $boolean
     *
     * @return self
     **/
    protected function fork(array $conditions, $boolean)
    {
        return new static($this->matcher, $conditions, $boolean);
    }
}