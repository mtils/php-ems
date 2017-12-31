<?php
/**
 *  * Created by mtils on 29.12.17 at 09:49.
 **/

namespace Ems\Core;

use Countable;
use DateTimeInterface;
use Ems\Contracts\Core\AppliesToResource;
use Ems\Contracts\Core\Checker as CheckerContract;
use Ems\Contracts\Core\PointInTime as PointInTimeContract;
use Ems\Contracts\Core\Type;
use Ems\Contracts\Expression\Constraint;
use Ems\Contracts\Expression\ConstraintGroup;
use Ems\Core\Exceptions\ConstraintViolationException;
use Ems\Core\Exceptions\NotImplementedException;
use Ems\Core\Patterns\ExtendableTrait;
use Ems\Core\Patterns\SnakeCaseCallableMethods;
use Ems\Expression\ConstraintParsingMethods;
use InvalidArgumentException;
use UnderflowException;
use const FILTER_FLAG_IPV4;
use const FILTER_VALIDATE_URL;
use const JSON_ERROR_NONE;
use const PREG_SPLIT_NO_EMPTY;
use function array_shift;
use function array_unique;
use function array_unshift;
use function date_parse;
use function filter_var;
use function func_get_args;
use function in_array;
use function is_array;
use function is_numeric;
use function is_object;
use function json_decode;
use function json_last_error;
use function mb_strlen;
use function method_exists;
use function simplexml_load_string;
use function strip_tags;
use function strtotime;

class Checker implements CheckerContract
{
    use ExtendableTrait;
    use ConstraintParsingMethods;
    use SnakeCaseCallableMethods;

    /**
     * This is the prefix for the "own check methods" of this class.
     *
     * @var string
     */
    protected $snakeCasePrefix = 'check';

    /**
     * {@inheritdoc}
     *
     * @param mixed                        $value
     * @param ConstraintGroup|array|string $rule
     * @param AppliesToResource            $resource (optional)
     *
     * @return bool
     */
    public function check($value, $rule, AppliesToResource $resource=null)
    {

        $constraints = $this->ruleToArray($rule);

        foreach ($constraints as $name => $parameters) {

            $arguments = $parameters;

            array_unshift($arguments, $value);

            if ($resource) {
                $arguments[] = $resource;
            }

            if (!$this->__call($name, $arguments)) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed                        $value
     * @param ConstraintGroup|array|string $rule
     * @param AppliesToResource            $resource (optional)
     *
     * @return bool (always true)
     *
     * @throws \Ems\Contracts\Core\Errors\ConstraintFailure
     */
    public function force($value, $rule, AppliesToResource $resource=null)
    {
        if (!$this->check($value, $rule, $resource)) {
            throw new ConstraintViolationException('Value does not match constraint.');
        }

        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $name
     *
     * @return bool
     */
    public function supports($name)
    {
        if ($this->hasExtension($name)) {
            return true;
        }

        return $this->getMethodBySnakeCaseName($name) != '';
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function names()
    {
        $all = array_merge($this->extensions(), array_keys($this->getSnakeCaseMethods()));
        return array_unique($all);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $name
     * @param array $arguments
     *
     * @return mixed
     */
    public function __call($name, array $arguments = [])
    {
        if (!$arguments) {
            throw new UnderflowException('You have to pass at least one parameter (value) to check something');
        }

        if ($this->hasExtension($name)) {
            return Lambda::callFast($this->getExtension($name), $arguments);
        }

        if ($methodName = $this->getMethodBySnakeCaseName($name)) {
            return $this->$methodName(...$arguments);
        }

        throw new NotImplementedException("Constraint '$name' is not supported.");

    }

    /**
     * Check if the value equals another value.
     *
     * @param mixed $value
     * @param mixed $other
     *
     * @return bool
     */
    public function checkEquals($value, $other)
    {
        return $value == $other;
    }

    /**
     * Check exactly if $value is $other.
     *
     * @param mixed $value
     * @param mixed $other
     *
     * @return bool
     */
    public function checkIs($value, $other)
    {
        return $this->checkCompare($value, 'is', $other);
    }

    /**
     * Check if $value is not exactly $other.
     *
     * @param mixed $value
     * @param mixed $other
     *
     * @return bool
     */
    public function checkIsNot($value, $other)
    {
        return $this->checkCompare($value, 'is not', $other);
    }

    /**
     * Check if the value is not equal to another value.
     *
     * @param mixed $value
     * @param mixed $other
     *
     * @return bool
     */
    public function checkNotEqual($value, $other)
    {
        return !$this->checkEquals($value, $other);
    }

    /**
     * Check if the passed value is set.
     *
     * @param $value
     *
     * @return bool
     */
    public function checkRequired($value)
    {
        if (is_null($value)) {
            return false;
        }

        if (is_string($value) && trim($value) === '') {
            return false;
        }

        if ((is_array($value) || $value instanceof Countable) && count($value) < 1) {
            return false;
        }

        return true;
    }

    /**
     * Check if the passed value is greater than or equals parameter.
     *
     * @param $value
     * @param $limit
     *
     * @return bool
     */
    public function checkMin($value, $limit)
    {
        return $this->checkCompare($value, '>=', $limit);
    }

    /**
     * Check if the passed value is less than or equals parameter.
     *
     * @param $value
     * @param $limit
     *
     * @return bool
     */
    public function checkMax($value, $limit)
    {
        return $this->checkCompare($value, '<=', $limit);
    }

    /**
     * Check if the passed value is greater than the parameter.
     *
     * @param $value
     * @param $limit
     *
     * @return bool
     */
    public function checkGreater($value, $limit)
    {
        return $this->checkCompare($value, '>', $limit);
    }

    /**
     * Check if the passed value is less than the parameter.
     *
     * @param $value
     * @param $limit
     *
     * @return bool
     */
    public function checkLess($value, $limit)
    {
        return $this->checkCompare($value, '<', $limit);
    }

    /**
     * Check if a value is between $min and $max. (inclusive)
     *
     * @param $value
     * @param $min
     * @param $max
     *
     * @return bool
     */
    public function checkBetween($value, $min, $max)
    {
        return $this->checkMin($value, $min) && $this->checkMax($value, $max);
    }

    /**
     * Check if $value has exactly $size.
     *
     * @param mixed $value
     * @param int $size
     *
     * @return bool
     */
    public function checkSize($value, $size)
    {
        return $this->checkCompare($this->getSize($value), '=' , $size);
    }

    /**
     * Check if $date is after $earliest.
     *
     * @param $date
     * @param $earliest
     *
     * @return bool
     */
    public function checkAfter($date, $earliest)
    {
        try {
            return $this->toTimestamp($date) > $this->toTimestamp($earliest);
        } catch (InvalidArgumentException $e) {
            return false;
        }
    }

    /**
     * Check if $date is before $earliest.
     *
     * @param $date
     * @param $latest
     *
     * @return bool
     */
    public function checkBefore($date, $latest)
    {
        try {
            return $this->toTimestamp($date) < $this->toTimestamp($latest);
        } catch (InvalidArgumentException $e) {
            return false;
        }
    }

    /**
     * Check if the value is of type $type. (class or type)
     *
     * @param mixed  $value
     * @param string $type
     *
     * @return bool
     */
    public function checkType($value, $type)
    {
        return Type::is($value, $type);
    }

    /**
     * Check if a value is an integer.
     *
     * @param $value
     *
     * @return bool
     */
    public function checkInt($value)
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    /**
     * Check if a value looks like a boolean.
     *
     * @param mixed $value
     *
     * @return bool
     */
    public function checkBool($value)
    {
        return in_array($value, [true, false, 0, 1, '0', '1', 'true', 'false'], true);
    }

    /**
     * Check if a value is numeric.
     *
     * @param $value
     *
     * @return bool
     */
    public function checkNumeric($value)
    {
        return is_numeric($value);
    }

    /**
     * Check if a value is a string.
     *
     * @param $value
     *
     * @return bool
     */
    public function checkString($value)
    {
        return Type::isStringLike($value);
    }

    /**
     * Compare $first and $second by $operator
     *
     * @param mixed  $left
     * @param string $operator
     * @param mixed  $right
     * @param bool   $strict (default:false)
     *
     * @return bool
     */
    public function checkCompare($left, $operator, $right, $strict=false)
    {

        if ($strict || in_array($operator, ['is', 'is not', '=', '!=', '<>'])) {
            return $this->compare($left, $operator, $right);
        }

        if (!$comparable = $this->makeComparable($left, $right)) {
            return false;
        }

        list($left, $right) = $comparable;

        return $this->compare($left, $operator, $right);
    }

    /**
     * Checks if a value would be counted as true.
     *
     * @param mixed $value
     *
     * @return bool
     */
    public function checkTrue($value)
    {
        return Type::toBool($value) === true;
    }

    /**
     * Checks if a value would be counted as true.
     *
     * @param mixed $value
     *
     * @return bool
     */
    public function checkFalse($value)
    {
        return Type::toBool($value) === false;
    }

    /**
     * Check if the passed $value is contained in $list.
     *
     * @param mixed $value
     * @param mixed $list
     *
     * @return bool
     */
    public function checkIn($value, $list)
    {
        // List is passed as second parameter or with many single parameters
        $args = func_get_args();
        array_shift($args); // remove $value

        $items = count($args) > 1 ? $args : (array)$list;

        return in_array($value, $items);
    }

    /**
     * Return true if the passed $value is not in $list.
     *
     * @param mixed $value
     * @param mixed $list
     *
     * @return bool
     */
    public function checkNotIn($value, $list)
    {
        $args = func_get_args();
        return !$this->checkIn(...$args);
    }

    /**
     * Check if the passed $value is a valid date.
     *
     * @param $value
     *
     * @return bool
     */
    public function checkDate($value)
    {

        if ($value instanceof PointInTimeContract) {
            return $value->isValid();
        }

        if ($value instanceof DateTimeInterface) {
            return true;
        }

        // Support for legacy datetime objects like Zend_Date
        if (is_object($value) && method_exists($value, 'getTimestamp')) {
            return $this->checkInt($value->getTimestamp());
        }

        if ((!Type::isStringLike($value) && !is_numeric($value)) || strtotime($value) === false) {
            return false;
        }

        $date = date_parse($value);

        return checkdate($date['month'], $date['day'], $date['year']);
    }

    /**
     * Check if the passed value is a valid email address
     *
     * @param string $value
     *
     * @return bool
     */
    public function checkEmail($value)
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Check if the passed value is a valid url.
     *
     * @param mixed $value
     *
     * @return bool
     */
    public function checkUrl($value)
    {
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Check if the passed value is a valid ip address.
     *
     * @param $value
     *
     * @return bool
     */
    public function checkIp($value)
    {
        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * Check if the passed value is a valid ip v4 address.
     *
     * @param $value
     *
     * @return bool
     */
    public function checkIpv4($value)
    {
        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }

    /**
     * Check if the passed value is a valid ip v4 address.
     *
     * @param $value
     *
     * @return bool
     */
    public function checkIpv6($value)
    {
        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }

    /**
     * Check if a number of $count were passed.
     *
     * @param string $value
     * @param int    $count
     *
     * @return bool
     */
    public function checkDigits($value, $count)
    {

        if (is_numeric($value)) {
            $value = "$value";
        }

        if (!Type::isStringLike($value)) {
            return false;
        }

        return ! preg_match('/[^0-9]/', "$value")
            && strlen("$value") == $count;
    }

    /**
     * Check if the passed $value is json.
     *
     * @param $value
     *
     * @return bool
     */
    public function checkJson($value)
    {
        if (!Type::isStringLike($value)) {
            return false;
        }

        json_decode($value);

        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Check if the passed $value is (valid) xml.
     *
     * @param $value
     *
     * @return bool
     */
    public function checkXml($value)
    {
        if (!Type::isStringLike($value)) {
            return false;
        }

        return (bool)@simplexml_load_string("$value");
    }

    /**
     * Checks if $value is html.
     *
     * @param $value
     *
     * @return bool
     */
    public function checkHtml($value)
    {
        if (!Type::isStringLike($value)) {
            return false;
        }

        $string = "$value";

        return $string != strip_tags($string);
    }

    /**
     * Checks if $value is no html.
     *
     * @param $value
     *
     * @return bool
     */
    public function checkPlain($value)
    {
        if (!Type::isStringable($value)) {
            return false;
        }

        $string = "$value";

        return $string == strip_tags($string);
    }

    /**
     * Check if a string is plain or has the allowed $htmlTags.
     * Note that because using strip_tags html comments are also not allowed
     * here. In opposite to strip_tags the tags are passed as an array.
     *
     * @param string       $value
     * @param string|array $htmlTags
     *
     * @return bool
     */
    public function checkTags($value, $htmlTags)
    {
        if (!Type::isStringable($value)) {
            return false;
        }

        $args = func_get_args();
        array_shift($args); // remove $value

        $htmlTags = count($args) > 1 ? $args : (array)$htmlTags;

        $tagString = '';

        foreach ($htmlTags as $tag) {
            $tag = trim($tag);
            $tagString .= "<$tag><$tag/><$tag />";
        }

        $string = "$value";

        return $string == strip_tags($string, $tagString);

    }

    /**
     * Check if a string has exactly $count chars. In opposite to checkSize it
     * will do an explicit cast to string and will not check the size of countable
     * objects.
     *
     * @param mixed $value
     * @param int $count
     *
     * @return bool
     */
    public function checkChars($value, $count)
    {
        if (!Type::isStringable($value)) {
            return false;
        }

        return mb_strlen("$value") == $count;
    }

    /**
     * Check if a string contains exactly $count words. Numbers are also words.
     *
     * @param mixed $value
     * @param int $count
     *
     * @return bool
     */
    public function checkWords($value, $count)
    {
        if (!Type::isStringLike($value)) {
            return $count == 0;
        }

        return count(preg_split('~[^\p{L}\p{N}\']+~u', $value, -1, PREG_SPLIT_NO_EMPTY)) == $count;
    }

    /**
     * Check if a string starts with $start.
     *
     * @param string $value
     * @param string $start
     *
     * @return bool
     */
    public function checkStartsWith($value, $start)
    {
        if (!Type::isStringable($value)) {
            return false;
        }

        return Helper::startsWith("$value", $start);
    }

    /**
     * Check if a string ends with $start.
     *
     * @param string $value
     * @param string $end
     *
     * @return bool
     */
    public function checkEndsWith($value, $end)
    {
        if (!Type::isStringable($value)) {
            return false;
        }

        return Helper::endsWith("$value", $end);
    }

    /**
     * Check if $value is somewhere inside $value. Strings and arrays are supported.
     *
     * @param string|array $value
     * @param mixed $needle
     *
     * @return bool
     */
    public function checkContains($value, $needle)
    {
        return Helper::contains($value, $needle);
    }

    /**
     * To a sql like match on $pattern.
     *
     * @param string $value
     * @param string $pattern
     * @param string $escape (default: \)
     *
     * @return bool
     */
    public function checkLike($value, $pattern, $escape='\\')
    {

        if (!Type::isStringable($value) || !Type::isStringable($pattern)) {
            return false;
        }

        $value = "$value";
        $pattern = "$pattern";

        // @see https://stackoverflow.com/questions/11434305/simulating-like-in-php

        // Split the pattern into special sequences and the rest
        $expr = '/((?:'.preg_quote($escape, '/').')?(?:'.preg_quote($escape, '/').'|%|_))/';
        $parts = preg_split($expr, $pattern, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        // Loop the split parts and convert/escape as necessary to build regex
        $expr = '/^';

        $lastWasPercent = false;

        foreach ($parts as $part) {
            switch ($part) {
                case $escape.$escape:
                    $expr .= preg_quote($escape, '/');
                    break;
                case $escape.'%':
                    $expr .= '%';
                    break;
                case $escape.'_':
                    $expr .= '_';
                    break;
                case '%':
                    if (!$lastWasPercent) {
                        $expr .= '.*?';
                    }
                    break;
                case '_':
                    $expr .= '.';
                    break;
                default:
                    $expr .= preg_quote($part, '/');
                    break;
            }

            $lastWasPercent = $part == '%';

        }

        $expr .= '$/i';

        // Look for a match and return bool
        return (bool)preg_match($expr, $value);
    }

    /**
     * Return true if $value matches $pattern.
     *
     * @param mixed $value
     * @param string $pattern
     *
     * @return bool
     */
    public function checkRegex($value, $pattern)
    {

        if (!Type::isStringable($value)) {
            return false;
        }

        return preg_match($pattern, "$value") > 0;
    }

    /**
     * Check that a string contains only alphabetic chars.
     *
     * @param $value
     *
     * @return bool
     */
    public function checkAlpha($value)
    {
        return $this->checkRegex($value, '/^[\pL\pM]+$/u');
    }

    /**
     * Check that a string contains only alphanumeric chars and dashes (- and _).
     *
     * @param $value
     *
     * @return bool
     */
    public function checkAlphaDash($value)
    {
        return $this->checkRegex($value, '/^[\pL\pM\pN_-]+$/u');
    }

    /**
     * Check that a string contains alphanumeric chars.
     *
     * @param $value
     *
     * @return bool
     */
    public function checkAlphaNum($value)
    {
        return $this->checkRegex($value, '/^[\pL\pM\pN]+$/u');
    }

    /**
     * Make two values comparable by normal operators.
     *
     * @param $value
     * @param $parameter
     *
     * @return array
     */
    protected function makeComparable($value, $parameter)
    {

        if (!$value instanceof DateTimeInterface && !$parameter instanceof DateTimeInterface) {
            return [$this->getSize($value), $parameter];
        }

        // One is datetime
        try {
            $value = $this->toTimestamp($value);
            $parameter = $this->toTimestamp($parameter);
            return [$value, $parameter];
        } catch (InvalidArgumentException $e) {
            return [];
        }

    }

    /**
     * Try to convert a arbitrary date parameter into a timestamp.
     *
     * @param mixed $date
     *
     * @return int
     */
    protected function toTimestamp($date)
    {
        return PointInTime::guessFrom($date)->getTimestamp();
    }

    /**
     * Try to guess the size of a parameter.
     *
     * @param mixed $value
     *
     * @return int
     */
    protected function getSize($value)
    {
        if (is_numeric($value)) {
            return $value;
        }

        if (is_array($value) || $value instanceof Countable) {
            return count($value);
        }

        return mb_strlen($value);
    }

    /**
     * @param $rule
     *
     * @return array
     */
    protected function ruleToArray($rule)
    {
        if ($rule instanceof ConstraintGroup || $rule instanceof Constraint) {
            return $this->constraintToArray($rule);
        }

        return $this->parseConstraint($rule);
    }

    /**
     * @param Constraint|ConstraintGroup $rule
     *
     * @return array
     */
    protected function constraintToArray($rule)
    {
        if ($rule instanceof Constraint) {
            return [
                $rule->name() => $rule->parameters()
            ];
        }

        /** @var ConstraintGroup $rule */

        $array = [];

        /** @var Constraint $constraint */
        foreach ($rule->constraints() as $constraint) {
            $array[$constraint->name()] = $constraint->parameters();
        }

        return $array;
    }

    /**
     * Compare $left and $right by $operator.
     *
     * @param mixed  $left
     * @param string $operator
     * @param mixed  $right
     *
     * @return bool
     */
    protected function compare($left, $operator, $right)
    {
        switch ($operator) {
            case '<':
                return $left < $right;
            case '>':
                return $left > $right;
            case '<=':
                return $left <= $right;
            case '>=':
                return $left >= $right;
            case '!=':
            case '<>':
                return $left != $right;
            case '=':
                return $left == $right;
            case 'is':
                return $left === $right;
            case 'is not':
                return $left !== $right;
            default:
                throw new InvalidArgumentException("Unknown operator '$operator");
        }
    }
}