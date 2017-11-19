<?php

namespace Ems\Core\Exceptions;

use LengthException;
use Ems\Contracts\Core\Errors\SyntaxError;

/**
 * Throw a KeyLengthException if a key is too short or too long.
 * It is currently thrown in storage implementations with a min key length.
 **/
class KeyLengthException extends LengthException implements SyntaxError
{
    /**
     * @var int
     */
    protected $minLength = 0;

    /**
     * @var int
     */
    protected $maxLength = PHP_INT_MAX;

    /**
     * @var int
     */
    protected $minSegments = 0;

    /**
     * @var int
     */
    protected $maxSegments = PHP_INT_MAX;

    /**
     * Return the key minimum length.
     *
     * @return int
     */
    public function getMinLength()
    {
        return $this->minLength;
    }

    /**
     * Set the minimum key length.
     *
     * @param int $minLength
     *
     * @return $this
     */
    public function setMinLength($minLength)
    {
        $this->minLength = $minLength;
        return $this;
    }

    /**
     * Get the maximum key length.
     *
     * @return int
     */
    public function getMaxLength()
    {
        return $this->maxLength;
    }

    /**
     * Set the maximum key length.
     *
     * @param int $maxLength
     *
     * @return $this
     */
    public function setMaxLength($maxLength)
    {
        $this->maxLength = $maxLength;
        return $this;
    }

    /**
     * Return the minimum key segments. (e.g. 2 for de.messages)
     *
     * @return int
     */
    public function getMinSegments()
    {
        return $this->minSegments;
    }

    /**
     * Set the minimum key segments.
     *
     * @param int $minSegments
     *
     * @return $this
     *
     * @see $this->getMinSegments()
     */
    public function setMinSegments($minSegments)
    {
        $this->minSegments = $minSegments;
        return $this;
    }

    /**
     * Return the maximum amount of key segments.
     *
     * @return int
     *
     * @see $this->getMinSegments()
     */
    public function getMaxSegments()
    {
        return $this->maxSegments;
    }

    /**
     * Set the maximum key segments.
     *
     * @param int $maxSegments
     *
     * @return $this
     */
    public function setMaxSegments($maxSegments)
    {
        $this->maxSegments = $maxSegments;
        return $this;
    }

}
