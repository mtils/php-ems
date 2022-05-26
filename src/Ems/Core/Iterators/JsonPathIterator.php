<?php
/**
 *  * Created by mtils on 11.04.2022 at 21:59.
 **/

namespace Ems\Core\Iterators;

use Closure;
use Ems\Core\Exceptions\UnsupportedParameterException;
use Iterator;
use RecursiveArrayIterator;

use function array_pop;
use function call_user_func;
use function dd;
use function explode;
use function implode;
use function in_array;
use function is_int;
use function str_split;
use function strpos;
use function var_export;

/*
 *
 *
 * Return every node and the json path as its key
 * [
 *   'name' => 'Michael',                  $.name
 *   'projects' => [                       $.projects
 *     [                                   $.projects[0]
 *       'name' => 'Children help'         $.projects[0].name
 *     ]
 *   ],
 *   'address' => [                        $.address
 *       'street' => 'Elm Street'          $.address.street
 *   ]
 * ]
 */
class JsonPathIterator implements Iterator
{

    /**
     * @var callable|null
     */
    protected $matcher;

    /**
     * @var array
     */
    protected $source = [];

    /**
     * @var string
     */
    protected $expression = '';

    /**
     * @var array[]
     */
    protected $stack = [];

    /**
     * @var string[]
     */
    protected $selectorStack = [];

    /**
     * @var int
     */
    protected $stackPosition = -1;

    /**
     * @var int
     */
    protected $maxPosition = 0;

    /**
     * @var string
     */
    protected $keyPrefix = '$';

    /**
     * @var bool
     */
    protected $rootIsList = null;

    public function __construct(array $source=[], string $expression='', callable $matcher=null)
    {
        $this->source = $source;
        $this->expression = $expression;
        $this->matcher = $matcher;
    }

    public function valid(): bool
    {
        if ($this->stackPosition != $this->maxPosition) {
            $this->stackPosition += 1;
            $this->selectorStack[] = $this->formatSegment($this->stack[$this->stackPosition]['key'], $this->stackPosition);
        }

        if (!$iterator = $this->sourceIterator()) {
            return false;
        }

        if (!$iterator->valid()) {
            if ($this->stackPosition < 1) {
                return false;
            }
            $this->pop();
            return $this->valid();
        }

        if ($iterator->hasChildren()) {
            $this->push($iterator->getChildren(), $iterator->key(), false);
        }

        if (!$this->matcher) {
            return true;
        }

        $path = $this->key();

        if (!call_user_func($this->matcher, $path, $this->splitPath($path), $this->current())) {
            $this->next();
            return $this->valid();
        }

        return true;
    }

    /**
     * @return mixed|null
     */
    public function current()
    {
        if (!$iterator = $this->sourceIterator()) {
            return null;
        }
        return $iterator->current();
    }

    /**
     * Get the absolute json path expression to the current node.
     *
     * @return string
     */
    public function key() : string
    {
        if (!$iterator = $this->sourceIterator()) {
            return '';
        }

        $stack = $this->selectorStack;

        $stack[] = $this->formatSegment($iterator->key(), $this->stackPosition+1);

        $selector = implode($stack);
        if (!$this->keyPrefix) {
            return $selector;
        }
        return $selector[0] == '[' ? $this->keyPrefix . $selector : $this->keyPrefix . '.' . $selector;
    }

    /**
     * Move cursor forward
     * @return void
     */
    public function next()
    {
        if ($iterator = $this->sourceIterator()) {
            $iterator->next();
        }
    }

    /**
     * Rewind the cursor. This is typically done at start of foreach
     * @return void
     */
    public function rewind()
    {
        $this->stack = [];
        $this->stackPosition = -1;
        $this->rootIsList = null;
        if (!$this->source) {
            return;
        }
        $this->push(new RecursiveArrayIterator($this->source));
        if (!$this->shouldMatchAll($this->expression)) {
            $this->matcher = $this->createMatcher($this->expression);
        }
    }

    /**
     * Get the expression (that was passed in __construct)
     * @return string
     */
    public function getExpression() : string
    {
        return $this->expression;
    }

    /**
     * Get the source array.
     *
     * @return array
     */
    public function getSource() : array
    {
        return $this->source;
    }

    /**
     * Set the source array.
     *
     * @param array $source
     * @return $this
     */
    public function setSource(array $source) : JsonPathIterator
    {
        $this->source = $source;
        return $this;
    }

    /**
     * @return string
     */
    public function getKeyPrefix(): string
    {
        return $this->keyPrefix;
    }

    /**
     * @param string $keyPrefix
     * @return self
     */
    public function setKeyPrefix(string $keyPrefix): JsonPathIterator
    {
        $this->keyPrefix = $keyPrefix;
        return $this;
    }

    /**
     * @return callable
     */
    public function getMatcher(): callable
    {
        if (!$this->matcher) {
            $this->matcher = $this->createMatcher($this->getExpression());
        }
        return $this->matcher;
    }

    /**
     * Traverse into a child.
     *
     * @param RecursiveArrayIterator    $iterator
     * @param int|string                $key
     * @param bool                      $raisePosition (optional)
     * @return void
     */
    protected function push(RecursiveArrayIterator $iterator, $key=null, bool $raisePosition=true)
    {
        $this->stack[] = [
            'iterator'  => $iterator,
            'key'       =>  $key
        ];
        $this->maxPosition = count($this->stack)-1;
        if ($this->rootIsList === null && $this->maxPosition === 1) {
            $this->rootIsList = is_int($key);
        }
        if ($raisePosition) {
            $this->stackPosition = $this->maxPosition;
        }
    }

    /**
     * Split a json path into its segments
     *
     * @param string $expression
     * @return array
     */
    public function splitPath(string $expression) : array
    {
        $expression = ltrim($expression, '$.');
        $stack = [];
        $stackPosition = $expression[0] == '[' ? -1 : 0;
        foreach (str_split($expression) as $char) {
            if ($char == '.') {
                $stackPosition++;
                continue;
            }
            if ($char == '[') {
                $stackPosition++;
            }
            if (!isset($stack[$stackPosition])) {
                $stack[$stackPosition] = '';
            }
            $stack[$stackPosition] .= $char;
        }
        return $stack;
    }

    /**
     * Finish traversal of a child
     *
     * @return void
     */
    protected function pop()
    {
        array_pop($this->stack);
        $this->maxPosition = count($this->stack)-1;
        $this->stackPosition = $this->maxPosition;
        array_pop($this->selectorStack);
    }

    /**
     * @return RecursiveArrayIterator|null
     */
    protected function sourceIterator(): ?RecursiveArrayIterator
    {
        if ($this->stackPosition !== -1) {
            return $this->stack[$this->stackPosition]['iterator'];
        }
        return null;
    }

    /**
     * Add a dot or brackets around a key to make it a segment.
     *
     * @param int|string $key
     * @param int        $stackPosition
     * @return string
     */
    protected function formatSegment($key, int $stackPosition) : string
    {
        if (is_int($key)) {
            return "[$key]";
        }
        if (!$this->rootIsList && $stackPosition < 2) {
            return $key;
        }
        return ".$key";
    }

    protected function createMatcher(string $expression) : Closure
    {

        if ($this->shouldMatchAll($expression)) {
            return function () {
                return true;
            };
        }

        if (!$this->isComplexExpression($expression)) {
            return function (string $path, array $pathStack, $value) use ($expression) {
                return $path == $expression;
            };
        }

        $this->failOnUnsupportedExpression($expression);

        $parsedExpression = $this->splitPath($expression);

        return function (string $path, array $pathStack, $value) use ($expression, $parsedExpression) {

            if ($expression == $path) {
                return true;
            }

            if (count($parsedExpression) != count($pathStack)) {
                return false;
            }

            foreach ($parsedExpression as $i=>$segment) {
                if (!isset($pathStack[$i])) {
                    return false;
                }
                $isArray = false;
                $criterion = $segment;
                if ($segment[0] == '[') {
                    $criterion = trim($segment, '[]');
                    $isArray = true;
                }

                if ($criterion == '*') {
                    continue;
                }

                if ($pathStack[$i] == $criterion) {
                    continue;
                }

                if (!$isArray) {
                    return false;
                }

                $pathSegment = trim($pathStack[$i], '[]');

                if (strpos($criterion, ',') !== false) {
                    if (!$this->matchesIndexList($criterion, $pathSegment)) {
                        return false;
                    }
                    continue;
                }

                if (strpos($criterion, ':') === false) {
                    return false;
                }

                echo "\n$criterion matches " . $pathStack[$i];
            }

            return true;
        };
    }

    /**
     * @param string $expression
     * @return bool
     */
    protected function shouldMatchAll(string $expression) : bool
    {
        if (!$expression || ($this->keyPrefix . $expression) == '$..*') {
            return true;
        }
        return false;
    }

    /**
     * Check if a complex matcher is needed.
     *
     * @param string $expression
     * @return bool
     */
    protected function isComplexExpression(string $expression) : bool
    {
        foreach (['*', ',', ':'] as $criterion) {
            if (strpos($expression, $criterion) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Match a comma separated list of numbers to an index.
     *
     * @param string $criterion
     * @param string $pathSegment
     *
     * @return bool
     */
    protected function matchesIndexList(string $criterion, string $pathSegment) : bool
    {
        return in_array($pathSegment, explode(',', $criterion));
    }

    protected function matchesSliceExpression(string $expression, string $pathSegment)
    {
        $parts = explode(':', $expression);
    }

    /**
     * @param string $expression
     * @return void
     */
    protected function failOnUnsupportedExpression(string $expression)
    {
        foreach (['..', '?', '@', '(', ')'] as $criterion) {
            if (strpos($expression, $criterion) !== false) {
                throw new UnsupportedParameterException("The json path expression '$criterion' is not supported");
            }
        }
    }
}