<?php


namespace Ems\Events;

use Ems\Core\Helper;
use Ems\Core\Lambda;
use Ems\Core\Exceptions\UnConfiguredException;
use Ems\Core\Exceptions\UnsupportedParameterException;
use InvalidArgumentException;

class MatchingListener
{
    /**
     * @var callable
     **/
    protected $call;

    /**
     * @var string
     **/
    protected $pattern = '*';

    /**
     * @var array
     **/
    protected $markFilter;

    /**
     * @param callable $call
     **/
    public function __construct(callable $call=null, $pattern='*', array $markFilter=[])
    {

        $this->setPattern($pattern);
        $this->setMarkFilter($markFilter);

        $this->call = $call ?: function () { throw new UnConfiguredException('No callable was assigned');};
    }

    /**
     * Return the setted event filter wildcard pattern.
     *
     * @return string
     **/
    public function getPattern()
    {
        return $this->pattern;
    }

    /**
     * Set a wildcard pattern to filter events.
     *
     * @param string $pattern
     *
     * @return self
     **/
    public function setPattern($pattern)
    {
        $this->pattern = $pattern;
        return $this;
    }

    /**
     * Return the mark filter array.
     *
     * @return array
     **/
    public function getMarkFilter()
    {
        return $this->markFilter;
    }

    /**
     * Set a new mark filter.
     *
     * @param array $markFilter
     *
     * @return self
     **/
    public function setMarkFilter(array $markFilter)
    {
        $this->markFilter = $markFilter;
        return $this;
    }

    /**
     * Call the callable if the marks (and perhaps the wildcard) match.
     *
     * @param array $args  (optional)
     * @param array $marks (optional)
     *
     * @return mixed
     **/
    public function callWithMarks(array $args=[], array $marks=[])
    {
        if (!$this->matchesMarks($marks)) {
            return;
        }

        if ($this->pattern === '*') {
            return Lambda::callFast($this->call, $args);
        }

        $event = array_shift($args);

        if ($this->matchesPattern($event)) {
            return Lambda::callFast($this->call, $args);
        }
    }

    /**
     * Call the callable if the pattern (and markFilter) applies.
     *
     * @return mixed
     **/
    public function __invoke()
    {

        if ($this->pattern === '!') {
            return;
        }

        if ($this->markFilter && !$this->matchesMarks([])) {
            return;
        }

        if ($this->pattern === '*') {
            return Lambda::callFast($this->call, func_get_args());
        }

        // If there is a pattern, the event name has to be the first arg

        $args = func_get_args();

        $event = array_shift($args);

        if ($this->matchesPattern($event)) {
            return Lambda::callFast($this->call, $args);
        }
    }

    /**
     * @param string $event
     * @param string $pattern
     *
     * @return bool
     **/
    public function matchesPattern($event)
    {
        if ($this->pattern === '*') {
            return true;
        }
        return fnmatch($this->pattern, $event);
    }

    /**
     * Manually check if passed marks matches the marksFilter.
     *
     * @param array $marks
     *
     * @return bool
     **/
    public function matchesMarks(array $marks)
    {
        if (!$this->markFilter) {
            return true;
        }

        foreach ($this->markFilter as $mark => $mustBe) {

            $markValue = isset($marks[$mark]) ? $marks[$mark] : null;

            // $mustBe has to be boolean or string, for performance reasons
            // it will not be checked again here

            if ($mustBe === true) {
                if (!isset($marks[$mark]) || !$marks[$mark]) {
                    return false;
                }
            }

            if ($mustBe === false) {
                if (!isset($marks[$mark])) {
                    continue;
                }
                if ((bool)$marks[$mark]) {
                    return false;
                }
            }

            // $mustBe cannot be boolean until here

            $shouldMatch = true;

            if (strpos($mustBe, '!') === 0) {
                $mustBe = substr($mustBe, 1);
                $shouldMatch = false;
            }

            if (!isset($marks[$mark]) && $shouldMatch) {
                return false;
            }

            if (!isset($marks[$mark]) && !$shouldMatch) {
                continue;
            }

            if ( ($marks[$mark] == $mustBe) !== $shouldMatch) {
                return false;
            }

        }

        return true;
    }

    /**
     * Create a new instance of this filter. This is used by Bus to create
     * new instances and allo dependency injection of others.
     *
     * @param callable $call       (optional)
     * @param string   $pattern    (default='*')
     * @param array    $markFilter (optional)
     *
     * @return self
     **/
    public function newInstance(callable $call=null, $pattern='*', array $markFilter=[])
    {
        return new static($call, $pattern, $markFilter);
    }

    /**
     * Make a readable mark call (of a bus) to a filterable mark array.
     *
     * @param string $mark
     * @param bool|string $value (optional)
     *
     * @return array
     **/
    public function markToArray($mark, $value = null, array $allowedMarks=[])
    {

        $givenMarks = $value !== null ? [$mark => $value] : (array)$mark;

        if (isset($givenMarks[0])) {
            $givenMarks = $this->indexedMarksToArray($givenMarks);
        }

        $marks = [];

        foreach ($givenMarks as $mark=>$value) {

            $this->checkParsedMark($mark, $value);

            if (!$allowedMarks) {
                continue;
            }

            if (!isset($allowedMarks[$mark])) {
                throw new UnsupportedParameterException("Mark $mark is not supported by this bus.");
            }
        }

        return $givenMarks;

    }

    /**
     * Allows shortcuts to boolean values by prefixing a mark with !.
     *
     * @param array $givenMarks
     *
     * @return array
     **/
    protected function indexedMarksToArray(array $givenMarks)
    {

        $marks = [];

        foreach ($givenMarks as $mark) {

            if (strpos($mark, '!') === 0) {
                $marks[substr($mark, 1)] = false;
                continue;
            }

            $marks[$mark] = true;

        }

        return $marks;
    }

    /**
     * Does some checks if the passed filter value is valid.
     *
     * @param string $mark
     * @param mixed  $value
     **/
    protected function checkParsedMark($mark, $value)
    {
        if (strpos($mark, '!') === 0) {
            throw new InvalidArgumentException("Leading ! cannot be used in associative marks.");
        }

        if (!is_bool($value) && !is_string($value)) {
            throw new UnsupportedParameterException("This MatchingListener can only match boolean and strings. You added for $mark a value of " . Helper::typeName($value));
        }
    }
}
