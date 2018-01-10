<?php
/**
 *  * Created by mtils on 29.12.17 at 08:00.
 **/

namespace Ems\Expression;

use Ems\Contracts\Core\Checker as CheckerContract;
use Ems\Contracts\Core\Exceptions\TypeException;
use Ems\Contracts\Core\Expression;
use Ems\Contracts\Core\Extractor as ExtractorContract;
use Ems\Contracts\Expression\Condition as ConditionContract;
use Ems\Contracts\Expression\ConditionGroup as ConditionGroupContract;
use Ems\Contracts\Expression\Constraint as ConstraintContract;
use Ems\Contracts\Expression\ConstraintGroup as ConstraintGroupContract;
use Ems\Contracts\Expression\Queryable;
use Ems\Core\Checker;
use Ems\Core\Exceptions\NotImplementedException;
use Ems\Core\Extractor;
use Ems\Core\Helper;
use Ems\Core\KeyExpression;
use Traversable;
use function array_pop;
use function array_shift;
use function is_array;
use function is_object;
use function strpos;
use function strtolower;

/**
 * Class Matcher
 *
 * The Matcher matches arbitrary data against an expression.
 *
 * @package Ems\Expression
 */
class Matcher implements Queryable
{
    /**
     * @var CheckerContract
     */
    protected $checker;

    /**
     * @var ExtractorContract
     */
    protected $extractor;

    /**
     * Matcher constructor.
     *
     * @param CheckerContract $checker (optional)
     * @param ExtractorContract $extractor (optional)
     */
    public function __construct(CheckerContract $checker=null, ExtractorContract $extractor=null)
    {
        $this->checker = $checker ?: new Checker();
        $this->extractor = $extractor ?: new Extractor();
    }

    /**
     * Return true if $data matches $expression
     *
     * @param mixed      $data
     * @param Expression $expression
     *
     * @return bool
     *
     * @throws TypeException
     */
    public function matches($data, Expression $expression)
    {
        $matcher = $this->compile($expression);
        return $matcher($data);
    }

    /**
     * Create a callable to match $expression. Useful if you know that the
     * expression will not change.
     *
     * @param Expression $expression
     *
     * @return callable
     */
    public function compile(Expression $expression)
    {

        if ($expression instanceof ConstraintContract || $expression instanceof ConstraintGroupContract) {
            return $this->compileConstraint($expression);
        }

        if ($expression instanceof ConditionContract) {
            return $this->compileCondition($expression);
        }

        if ($expression instanceof ConditionGroupContract) {
            return $this->compileConditionGroup($expression);
        }

        throw new TypeException('Matcher can only match Constraint, ConstraintGroup, Condition and ConditionGroup');

    }

    /**
     * {@inheritdoc}
     *
     * @param string|\Ems\Contracts\Core\Expression|\Closure $operand
     * @param mixed $operatorOrValue (optional)
     * @param mixed $value
     *
     * @return MatcherQuery
     **/
    public function where($operand, $operatorOrValue = null, $value = null)
    {
        $numArgs = func_num_args();
        $query = $this->newQuery();

        if ($numArgs == 1) {
            return $query->where($operand);
        }

        if ($numArgs == 2) {
            return $query->where($operand, $operatorOrValue);
        }

        return $query->where($operand, $operatorOrValue, $value);
    }


    /**
     * Make a Closure to match a Condition.
     *
     * @param ConditionContract $condition
     *
     * @return \Closure
     */
    protected function compileCondition(ConditionContract $condition)
    {

        $operand = $condition->operand();

        $matcher = $this->compile($condition->constraint());

        return function ($data) use ($operand, $matcher) {
            return $matcher($this->value($data, $operand));
        };

    }

    /**
     * Make a Closure to match a ConditionGroup.
     *
     * @param ConditionGroupContract $group
     *
     * @return \Closure
     */
    protected function compileConditionGroup(ConditionGroupContract $group)
    {
        $matchers = [];

        foreach ($group->conditions() as $condition) {
            $matchers[] = $this->compile($condition);
        }

        $operator = $group->operator();

        if (strtolower($operator) == 'and') {
            return function ($data) use ($matchers) {
                return $this->matchAnd($data, $matchers);
            };
        }

        if (strtolower($operator) == 'or') {
            return function ($data) use ($matchers) {
                return $this->matchOr($data, $matchers);
            };
        }

        throw new NotImplementedException("Operator '$operator' is currently not supported.");

    }

    /**
     * Compile a constraint or group.
     *
     * @param ConstraintContract|ConstraintGroupContract $constraint
     *
     * @return \Closure
     */
    protected function compileConstraint($constraint)
    {
        return function ($value) use ($constraint) {

            if (!$value instanceof MatchesCollection) {
                return $this->checker->check($value, $constraint);
            }

            // Matching values against sequential arrays will result in
            // checking if ANY of the values matches the criteria.
            // This will allow normal sql like "WHERE categories.id = 13" queries
            foreach ($value as $item) {
                if ($this->checker->check($item, $constraint)) {
                    return true;
                }
            }

            return false;

        };
    }

    /**
     * Return a value from subject.
     *
     * @param $root
     * @param string $path (optional)
     *
     * @return mixed
     */
    protected function value($root, $path=null)
    {
        if (!$path instanceof KeyExpression) {
            return $path instanceof Expression ? "$path" : $path;
        }

        if (!is_array($root) && !is_object($root)) {
            return $root;
        }

        $pathString = $path->toString();

        if ($value = $this->extractor->value($root, $pathString)) {
            return $value;
        }

        // If a "to many" relation is checked here (like categories.id)
        // the extractor will not be able to retrieve the id, because it will
        // look for $root->categories['id'] but it is a numeric array like
        // this: categories[0]->id. So we have to make a workaround for this
        if (!strpos($pathString, '.')) {
            return $value;
        }

        return $this->buildCollection($root, $pathString);

    }

    /**
     * @param Traversable|array $root
     * @param string            $path
     *
     * @return MatchesCollection|null
     */
    protected function buildCollection($root, $path)
    {

        $pathStack = explode('.', $path);
        $last = array_pop($pathStack);
        $lastIndex = count($pathStack) - 1;

        foreach ($pathStack as $i=>$segment) {

            $currentStack[] = $segment;
            $currentPath = implode('.', $currentStack);

            if (!$parent = $this->value($root, new KeyExpression($currentPath))) {
                break;
            }

            if (!Helper::isSequential($parent)) {
                array_shift($pathStack);
                $offsetPath = new KeyExpression(implode('.', $pathStack) . ".$last");
                return $this->value($parent, $offsetPath);
            }

            if ($i == $lastIndex) {

                $values = new MatchesCollection();

                foreach ($parent as $child) {
                    $values->append($this->extractor->value($child, $last));
                }

                return $values;
            }

            array_shift($pathStack);

            $offsetPath = implode('.', $pathStack) . ".$last";

            $values = new MatchesCollection();

            foreach ($parent as $child) {

                $collection = $this->buildCollection($child, $offsetPath);

                if (!$collection instanceof MatchesCollection) {
                    continue;
                }

                foreach ($collection as $hit) {
                    $values->append($hit);
                }
            }

            return $values;

        }

        return null;
    }

    /**
     * Check if ALL matchers match $data.
     *
     * @param $data
     * @param \Closure[] $matchers
     *
     * @return bool
     */
    protected function matchAnd($data, array $matchers)
    {
        foreach ($matchers as $matcher) {
            if (!$matcher($data)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check if ANY matcher match $data.
     *
     * @param $data
     * @param \Closure[] $matchers
     *
     * @return bool
     */
    protected function matchOr($data, array $matchers)
    {
        foreach ($matchers as $matcher) {
            if ($matcher($data)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return MatcherQuery
     **/
    protected function newQuery()
    {
        return (new MatcherQuery($this))
            ->allowConnectives('and', 'or');
    }

}