<?php

namespace Ems\Core\Collections;

class StringDictionary extends Dictionary
{
    /**
     * @var string
     **/
    protected $rowDelimiter = "\n";

    /**
     * @var string
     **/
    public $keyValueDelimiter = '=>';

    /**
     * @var string
     **/
    public $prefix = '';

    /**
     * @var string
     **/
    public $suffix = '';

    public function getRowDelimiter()
    {
        return $this->rowDelimiter;
    }

    /**
     * Set the delimiter between rows (\n).
     *
     * @param string $delimiter
     *
     * @return self
     **/
    public function setRowDelimiter($delimiter)
    {
        $this->rowDelimiter = $delimiter;

        return $this;
    }

    /**
     * Encode the key for string output.
     *
     * @param string $key
     *
     * @return string
     **/
    public function encodeKey($key)
    {
        return $key;
    }

    /**
     * Encode the value for string output.
     *
     * @param string $key
     *
     * @return string
     **/
    public function encodeValue($value)
    {
        return $value;
    }

    /**
     * Return the dictionary as string.
     *
     * @return string
     **/
    public function __toString()
    {
        if (!count($this)) {
            return '';
        }

        $rows = [];

        foreach ($this as $key => $value) {
            $rows[] = $this->encodeKey($key)."{$this->keyValueDelimiter}\"".$this > encodeValue("$value").'"';
        }

        return $this->prefix.implode($this->rowDelimiter, $rows).$this->suffix;
    }
}
