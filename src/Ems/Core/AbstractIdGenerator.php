<?php
/**
 *  * Created by mtils on 11.09.18 at 11:56.
 **/

namespace Ems\Core;


use Ems\Contracts\Core\Exceptions\TooManyIterationsException;
use Ems\Contracts\Core\IdGenerator;
use const PHP_INT_MAX;

abstract class AbstractIdGenerator implements IdGenerator
{
    /**
     * @var int
     */
    protected $min = 1;

    /**
     * @var int
     */
    protected $max = PHP_INT_MAX;

    /**
     * @var string
     */
    protected $idType = 'int';

    /**
     * @var int
     */
    protected $strength = 0;

    /**
     * @var callable
     */
    protected $isUniqueChecker;

    /**
     * How many tries
     *
     * @var int
     */
    protected $maxAttempts = 100000;

    public function __construct(callable $isUniqueChecker=null)
    {
        $this->isUniqueChecker = $isUniqueChecker;
    }

    /**
     * Implement this method to generate an id.
     *
     * @param mixed $salt (optional)
     * @param int $length (default:0)
     * @param bool $asciiOnly (default:true)
     *
     * @return mixed
     */
    protected abstract function generateFresh($salt = null, $length = 0, $asciiOnly = true);

    /**
     * @inheritDoc
     */
    public final function generate($salt = null, $length = 0, $asciiOnly = true)
    {
        if (!$this->isUniqueChecker) {
            return $this->generateFresh($salt, $length, $asciiOnly);
        }

        for ($i=0; $i<$this->maxAttempts; $i++) {

            $id = $this->generateFresh($salt, $length, $asciiOnly);

            if (call_user_func($this->isUniqueChecker, $id)) {
                return $id;
            }
        }

        throw new TooManyIterationsException("Giving up after $i tries to find a unique id");

    }

    /**
     * @inheritDoc
     */
    public function until(callable $isUniqueChecker)
    {
        return $this->replicate($isUniqueChecker);
    }

    /**
     * @inheritDoc
     */
    public function idType()
    {
        return $this->idType;
    }

    /**
     * @inheritDoc
     */
    public function min()
    {
        return $this->min;
    }

    /**
     * @inheritDoc
     */
    public function max()
    {
        return $this->max;
    }

    /**
     * @inheritDoc
     */
    public function strength()
    {
        return $this->strength;
    }

    /**
     * @inheritDoc
     */
    public function isSupported()
    {
        return true;
    }


    /**
     *
     *
     * @param callable $isUniqueChecker
     * @param array $params [optional]
     *
     *
     * @return static
     */
    protected function replicate(callable $isUniqueChecker, /** @noinspection PhpUnusedParameterInspection */ array $params=[])
    {
        return new static($isUniqueChecker);
    }
}