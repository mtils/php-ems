<?php
/**
 *  * Created by mtils on 08.05.2022 at 08:39.
 **/

namespace Ems\Validation;

use DateTime;
use Ems\Contracts\Core\PointInTime as PointInTimeContract;
use Ems\Contracts\Core\Type;
use Ems\Contracts\Expression\Constraint;
use Ems\Contracts\Expression\ConstraintGroup;
use Ems\Contracts\Validation\Validator as ValidatorContract;
use Ems\Core\PointInTime;

use function class_exists;
use function is_float;
use function is_int;
use function is_string;
use function str_replace;

class Caster
{
    /**
     * @var string
     */
    protected $defaultDecimalSeparator = '.';

    /**
     * @var string
     */
    protected $defaultThousandsSeparator = ',';

    /**
     * Cast the value into something a repository can process.
     *
     * @param mixed                                   $value
     * @param ConstraintGroup|Constraint|array|string $rule
     * @param object|null                             $ormObject (optional)
     * @param array                                   $formats (optional)
     *
     * @return mixed
     */
    public function __invoke($value, $rule, $ormObject=null, array $formats=[])
    {
        if (is_string($rule)) {
            return $this->__invoke($value, [$rule=>[]], $ormObject, $formats);
        }
        foreach ($rule as $constraint=>$params) {
            if ($this->constraintCastsToBool($constraint, $params)) {
                return $this->castToBool($value, $formats);
            }
            if ($this->constraintCastsToInt($constraint, $params)) {
                return $this->castToInt($value, $formats);
            }
            if ($this->constraintCastsToFloat($constraint, $params)) {
                return $this->castToFloat($value, $formats);
            }
            if ($this->constraintCastsToDateTime($constraint, $params)) {
                $format = $this->getDateFormat($constraint, $params, $formats);
                $dateTime = $this->castToDateTime($value, $format);
                if ($constraint == 'date' && $dateTime instanceof PointInTime) {
                    $dateTime->precision = PointInTimeContract::DAY;
                }
                return $dateTime;
            }
        }
        return $value;
    }

    /**
     * @param mixed $value
     * @param array $formats
     * @return bool
     *
     * @noinspection PhpUnusedParameterInspection
     */
    public function castToBool($value, array $formats=[]) : bool
    {
        return Type::toBool($value);
    }

    /**
     * @param mixed $value
     * @param array $formats
     * @return int
     */
    public function castToInt($value, array $formats=[]) : int
    {
        if (is_int($value)) {
            return $value;
        }
        return (int)$this->castToFloat($value, $formats);
    }

    /**
     * @param mixed $value
     * @param array $formats
     * @return float
     */
    public function castToFloat($value, array $formats=[]) : float
    {
        if (is_float($value)) {
            return $value;
        }
        $cleaned = str_replace($this->thousandsSeparator($formats), '', $value);
        $decimalSeparator = $this->decimalSeparator($formats);
        if ($decimalSeparator != '.') {
            $cleaned = str_replace($decimalSeparator, '.', $value);
        }
        return (float)$cleaned;
    }

    /**
     * @param $value
     * @param string $format
     * @return DateTime
     */
    public function castToDateTime($value, string $format='') : DateTime
    {
        if (class_exists(PointInTime::class)) {
            return $format ? PointInTime::createFromFormat($format, $value) : PointInTime::guessFrom($value);
        }
        return $format ? DateTime::guessFrom($value) : DateTime::createFromFormat($format, $value);
    }

    public function constraintCastsToFloat(string $constraint, array $params=[]) : bool
    {
        return $constraint == 'numeric' || $this->isOfType($constraint, $params, 'float');;
    }

    public function constraintCastsToInt(string $constraint, array $params=[]) : bool
    {
        return $this->isOfType($constraint, $params, 'int');
    }

    public function constraintCastsToBool(string $constraint, array $params=[]) : bool
    {
        return $this->isOfType($constraint, $params, ['bool','boolean']);
    }

    /**
     * @param string $constraint
     * @param array $parameters
     * @return bool
     */
    public function constraintCastsToDateTime(string $constraint, array $parameters=[]) : bool
    {
        return in_array($constraint, ['date','after','before']) || $this->isOfType($constraint, $parameters, 'datetime');
    }

    /**
     * @param array $formats
     * @return string
     */
    public function decimalSeparator(array $formats=[]) : string
    {
        return $formats[ValidatorContract::DECIMAL_SEPARATOR] ?? $this->getDefaultDecimalSeparator();
    }

    /**
     * @param array $formats
     * @return string
     */
    public function thousandsSeparator(array $formats=[]) : string
    {
        return $formats[ValidatorContract::THOUSANDS_SEPARATOR] ?? $this->getDefaultThousandsSeparator();
    }

    /**
     * @return string
     */
    public function getDefaultDecimalSeparator(): string
    {
        return $this->defaultDecimalSeparator;
    }

    /**
     * @param string $defaultDecimalSeparator
     */
    public function setDefaultDecimalSeparator(string $defaultDecimalSeparator): void
    {
        $this->defaultDecimalSeparator = $defaultDecimalSeparator;
    }

    /**
     * @return string
     */
    public function getDefaultThousandsSeparator(): string
    {
        return $this->defaultThousandsSeparator;
    }

    /**
     * @param string $defaultThousandsSeparator
     */
    public function setDefaultThousandsSeparator(string $defaultThousandsSeparator): void
    {
        $this->defaultThousandsSeparator = $defaultThousandsSeparator;
    }

    /**
     * Check if the constraint is of type. Pass multiple types to check if it is
     * ANY of the types (or).
     *
     * @param string $constraint
     * @param array $params
     * @param string|string[] $type
     * @return bool
     */
    protected function isOfType(string $constraint, array $params, $type) : bool
    {
        if (!is_array($type)) {
            return $constraint == $type || ($constraint == 'type' && $params == [$type]);
        }
        foreach ($type as $singleType) {
            if ($this->isOfType($constraint, $params, $singleType)) {
                return true;
            }
        }
        return false;
    }

    protected function getDateFormat(string $constraint, array $params, array $formats) : string
    {
        if (($constraint == 'after' || $constraint == 'before') && isset($params[1])) {
            return $params[1];
        }
        if (($constraint == 'date' || $constraint == 'datetime') && isset($params[0])) {
            return $params[0];
        }
        if ($constraint == 'date' && isset($formats[ValidatorContract::DATE_FORMAT])) {
            return $formats[ValidatorContract::DATE_FORMAT];
        }
        return $formats[ValidatorContract::DATETIME_FORMAT] ?? '';
    }
}