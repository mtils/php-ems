<?php
/**
 *  * Created by mtils on 11.09.18 at 11:36.
 **/

namespace Ems\Core\IdGenerator;


use Ems\Contracts\Core\Type;
use Ems\Core\AbstractIdGenerator;
use Ems\Core\Exceptions\UnsupportedParameterException;
use Ems\Core\Exceptions\UnsupportedUsageException;
use function is_numeric;
use OutOfBoundsException;


class IncrementingIdGenerator extends AbstractIdGenerator
{
    /**
     * @var int
     */
    protected $step = 1;

    /**
     * @param int $min
     * @return $this
     */
    public function setMin($min)
    {
        if (!is_numeric($min)) {
            throw new UnsupportedParameterException('min has to be numeric');
        }
        if ($min >= $this->max) {
            throw new UnsupportedParameterException("Min has to be smaller than max ($this->max)");
        }
        $this->min = (int)$min;

        return $this;
    }

    /**
     * @param int $max
     * @return $this
     */
    public function setMax($max)
    {
        if (!is_numeric($max)) {
            throw new UnsupportedParameterException('max has to be numeric');
        }
        if ($max <= $this->min) {
            throw new UnsupportedParameterException("Max has to be greater than min ($this->min)");
        }
        $this->max = (int)$max;
        return $this;
    }

    /**
     * @inheritDoc
     *
     * If you now the old max() of your ids you can pass it as $salt here. Then max()+1
     * will be the next id.
     */
    protected function generateFresh($salt = null, $length = 0, $asciiOnly = true)
    {

        if ($length != 0) {
            throw new UnsupportedUsageException('You cannot increment and determine a length for the result');
        }

        if ($salt !== null && !is_numeric($salt)) {
            throw new UnsupportedParameterException('$salt has to be null or numeric not ' . Type::of($salt));
        }

        // The real min is $this->min - 1
        $min = $this->min-1;

        if ($salt !== null && ($salt < $min || $salt > $this->max)) {
            throw new OutOfBoundsException("Salt: $salt is not between min: $min and max: $this->max");
        }

        $last = $salt ? (int)$salt : $min;

        if ($this->failedAttempts) {
            $last = $this->failedAttempts;
        }

        $id = $last+$this->step;

        if ($id > $this->max) {
            throw new \OverflowException("The generated id $id is greater then the max $this->max.");
        }

        return $id;
    }

}