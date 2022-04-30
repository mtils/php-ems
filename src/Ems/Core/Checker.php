<?php
/**
 *  * Created by mtils on 29.12.17 at 09:49.
 **/

namespace Ems\Core;

use ArrayAccess;
use Countable;
use DateTimeInterface;
use Ems\Contracts\Core\Checker as CheckerContract;
use Ems\Contracts\Core\PointInTime as PointInTimeContract;
use Ems\Contracts\Core\Type;
use Ems\Contracts\Expression\Constraint;
use Ems\Contracts\Expression\ConstraintGroup;
use Ems\Contracts\Expression\ConstraintParsingMethods;
use Ems\Core\Exceptions\ConstraintViolationException;
use Ems\Core\Exceptions\NotImplementedException;
use Ems\Core\Patterns\ExtendableTrait;
use Ems\Core\Patterns\SnakeCaseCallableMethods;
use InvalidArgumentException;
use Traversable;
use UnderflowException;

use function array_shift;
use function array_unique;
use function array_unshift;
use function date_parse;
use function filter_var;
use function func_get_args;
use function gettype;
use function in_array;
use function is_array;
use function is_bool;
use function is_numeric;
use function is_object;
use function json_decode;
use function json_last_error;
use function mb_strlen;
use function method_exists;
use function simplexml_load_string;
use function strip_tags;
use function strtotime;

use const FILTER_FLAG_IPV4;
use const FILTER_VALIDATE_URL;
use const JSON_ERROR_NONE;
use const PREG_SPLIT_NO_EMPTY;

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
     * @param mixed                                   $value
     * @param ConstraintGroup|Constraint|array|string $rule
     * @param object|null                             $ormObject (optional)
     *
     * @return bool
     */
    public function check($value, $rule, $ormObject=null) : bool
    {

        $constraints = $this->ruleToArray($rule);

        foreach ($constraints as $name => $parameters) {

            $arguments = $parameters;

            array_unshift($arguments, $value);

            if ($ormObject) {
                $arguments[] = $ormObject;
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
     * @param ConstraintGroup|Constraint|array|string $rule
     * @param object|null                  $ormObject (optional)
     *
     * @return bool (always true)
     *
     * @throws ConstraintViolationException
     */
    public function force($value, $rule, $ormObject=null) : bool
    {
        if (!$this->check($value, $rule, $ormObject)) {
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
    public function supports(string $name) : bool
    {
        if ($this->hasExtension($name)) {
            return true;
        }

        return $this->getMethodBySnakeCaseName($name) != '';
    }

    /**
     * {@inheritdoc}
     *
     * @return string[]
     */
    public function names() : array
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
    public function __call(string $name, array $arguments = [])
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
    public function checkEquals($value, $other) : bool
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
    public function checkIs($value, $other) : bool
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
    public function checkIsNot($value, $other) : bool
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
    public function checkNotEqual($value, $other) : bool
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
    public function checkRequired($value) : bool
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
    public function checkMin($value, $limit) : bool
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
    public function checkMax($value, $limit) : bool
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
    public function checkGreater($value, $limit) : bool
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
    public function checkLess($value, $limit) : bool
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
    public function checkBetween($value, $min, $max) : bool
    {
        return $this->checkMin($value, $min) && $this->checkMax($value, $max);
    }

    /**
     * Check if $value has exactly $size.
     *
     * @param mixed $value
     * @param $size
     *
     * @return bool
     */
    public function checkSize($value, $size) : bool
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
    public function checkAfter($date, $earliest) : bool
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
    public function checkBefore($date, $latest) : bool
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
    public function checkType($value, $type) : bool
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
    public function checkInt($value) : bool
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
    public function checkBool($value) : bool
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
    public function checkNumeric($value) : bool
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
    public function checkString($value) : bool
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
    public function checkCompare($left, string $operator, $right, bool $strict=false) : bool
    {

        if ($strict || in_array($operator, ['is', 'is not', '=', '!=', '<>'])) {
            return $this->isComparable($left, $right) && $this->compare($left, $operator, $right);
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
    public function checkTrue($value) : bool
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
    public function checkFalse($value) : bool
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
    public function checkIn($value, $list) : bool
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
    public function checkNotIn($value, $list) : bool
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
    public function checkDate($value) : bool
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
    public function checkEmail(string $value) : bool
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
    public function checkUrl($value) : bool
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
    public function checkIp($value) : bool
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
    public function checkIpv4($value) : bool
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
    public function checkIpv6($value) : bool
    {
        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }

    /**
     * Check if a number of $count were passed.
     *
     * @param mixed $value
     * @param int    $count
     *
     * @return bool
     */
    public function checkDigits($value, $count) : bool
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
    public function checkJson($value) : bool
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
    public function checkXml($value) : bool
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
    public function checkHtml($value) : bool
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
    public function checkPlain($value) : bool
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
    public function checkTags($value, $htmlTags) : bool
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
    public function checkChars($value, $count) : bool
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
    public function checkWords($value, $count) : bool
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
    public function checkStartsWith($value, $start) : bool
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
    public function checkEndsWith($value, $end) : bool
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
    public function checkContains($value, $needle) : bool
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
    public function checkLike($value, $pattern, $escape='\\') : bool
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
    public function checkRegex($value, $pattern) : bool
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
    public function checkAlpha($value) : bool
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
    public function checkAlphaDash($value) : bool
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
    public function checkAlphaNum($value) : bool
    {
        return $this->checkRegex($value, '/^[\pL\pM\pN]+$/u');
    }

    /**
     * Check the length. This is useful if you have strings that are numeric but
     * you want to count chars not the integer value.
     * Pass a numeric parameter to check $value == $parameter.
     * Pass a string with two numeric values divided by a minus to check
     * if the length is between two values ("3-45").
     *
     * @param mixed            $value
     * @param int|float|string $parameter
     * @return bool
     */
    public function checkLength($value, $parameter) : bool
    {
        $size = $this->getSize($value, false);

        if (is_numeric($parameter)) {
            return $size == $parameter;
        }
        $parts = explode('-',$parameter);
        if (count($parts) !== 2) {
            throw new InvalidArgumentException("checkLength either accepts a numeric value for equal comparison or two numbers divided by a minus.");
        }
        return $size >= $parts[0] && $size <= $parts[1];
    }

    /**
     * Check if something is an array (or behaves like an array)
     *
     * @param mixed $value
     * @return bool
     */
    public function checkArray($value) : bool
    {
        if (is_array($value)) {
            return true;
        }
        return $value instanceof ArrayAccess && $value instanceof Traversable && $value instanceof Countable;
    }

    /**
     * Make two values comparable by normal operators.
     *
     * @param $value
     * @param $parameter
     *
     * @return array
     */
    protected function makeComparable($value, $parameter) : array
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
     * Check if two values are comparable. ([] > 4 evaluates to true in php...)
     * This is somehow by definition...
     *
     * @param mixed $left
     * @param mixed $right
     *
     * @return bool
     */
    protected function isComparable($left, $right) : bool
    {

        if (gettype($left) == gettype($right)) {
            return true;
        }

        if (is_numeric($left) && is_numeric($right)) {
            return true;
        }

        // You can compare anything to bool and anything to null (really?...)
        return is_bool($left) || is_bool($right) || is_null($left) || is_null($right);

    }

    /**
     * Try to convert a arbitrary date parameter into a timestamp.
     *
     * @param mixed $date
     *
     * @return int
     */
    protected function toTimestamp($date) : int
    {
        return PointInTime::guessFrom($date)->getTimestamp();
    }

    /**
     * Try to guess the size of a parameter.
     *
     * @param mixed $value
     * @param bool $checkNumeric
     *
     * @return int
     */
    protected function getSize($value, bool $checkNumeric=true) : int
    {
        if ($checkNumeric && is_numeric($value)) {
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
    protected function ruleToArray($rule) : array
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
    protected function constraintToArray($rule) : array
    {
        if ($rule instanceof ConstraintGroup) {
            return $this->constraintGroupToArray($rule);
        }

        $operator = $rule->operator();

        // Do some more strict checks if constraints with operators were passed
        if (!$operator) {
            return [$rule->name() => $rule->parameters()];
        }

        if (strtolower($operator) == 'like') {
            return ['like' => $rule->parameters()];
        }

        return [
            'compare' => [
                $operator,
                $rule->parameters()[0],
                true
            ]
        ];
    }

    /**
     * @param ConstraintGroup $group
     *
     * @return array
     */
    protected function constraintGroupToArray(ConstraintGroup $group) : array
    {
        $array = [];

        foreach ($group->constraints() as $constraint) {
            foreach ($this->constraintToArray($constraint) as $key=>$value) {
                $array[$key] = $value;
            }
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
    protected function compare($left, string $operator, $right) : bool
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