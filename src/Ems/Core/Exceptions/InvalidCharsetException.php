<?php

namespace Ems\Core\Exceptions;

use Ems\Core\StringConverter\CharsetGuard;
use Exception;

/**
 * Throw a MisConfiguredException if an object was not configured propely
 **/
class InvalidCharsetException extends MisConfiguredException
{

    /**
     * @var string
     **/
    protected $failedString;

    /**
     * @var string
     **/
    protected $awaitedCharset;

    /**
     * @var CharsetGuard
     **/
    protected $guard;

    /**
     * @param string    $failedString
     * @param string    $awaitedCharset
     * @param Exception $previous (optional)
     **/
    public function __construct($failedString, $awaitedCharset, Exception $previous=null)
    {
        parent::__construct("String is not in $awaitedCharset", 0, $previous);
        $this->awaitedCharset = $awaitedCharset;
        $this->failedString = $failedString;
    }

    /**
     * @return string
     **/
    public function failedString()
    {
        return $this->failedString;
    }

    /**
     * @return string
     **/
    public function awaitedCharset()
    {
        return $this->awaitedCharset;
    }

    /**
     * Try to guess the correct charset
     *
     * @return string
     **/
    public function suggestedCharset()
    {
        return $this->guard()->detect($this->failedString());
    }

    /**
     * @return string
     **/
    public function getHelp()
    {
        $awaited = $this->awaitedCharset();
        $suggested = $this->suggestedCharset();

        if (!$suggested) {
            return "String should be encoded in $awaited but has an undetectable charset.";
        }

        return "String should be encoded in $awaited but seems to be $suggested";

    }

    /**
     * Set the guard to determine the charset.
     *
     * @param CharsetGuard $guard
     *
     * @return $this
     **/
    public function useGuard(CharsetGuard $guard)
    {
        $this->guard = $guard;
        return $this;
    }

    /**
     * @return CharsetGuard
     **/
    protected function guard()
    {
        if (!$this->guard) {
            $this->guard = new CharsetGuard;
        }

        return $this->guard;
    }

}
