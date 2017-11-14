<?php

namespace Ems\Core\Collections;

use Ems\Contracts\Core\Stringable;
use Ems\Core\Support\StringableTrait;

class StringList extends OrderedList implements Stringable
{
    use StringableTrait;

    /**
     * The delimiter.
     *
     * @var string
     **/
    protected $glue = ' ';

    /**
     * The string prefix for __toString.
     *
     * @var string
     **/
    protected $prefix = '';

    /**
     * The string suffix for __toString.
     *
     * @var string
     **/
    protected $suffix = '';

    /**
     * @param array|\Traversable|int|string $source (optional)
     * @param string                        $glue (default: ' ')
     * @param string                        $prefix (optional)
     * @param string                        $suffix (optional)
     *
     * @see self::setSource
     **/
    public function __construct($source = null, $glue = ' ', $prefix = '', $suffix = '')
    {
        $this->setGlue($glue)->setPrefix($prefix)->setSuffix($suffix);
        parent::__construct($source);
    }

    /**
     * Return the glue (string bewteen items).
     *
     * @return string
     **/
    public function getGlue()
    {
        return $this->glue;
    }

    /**
     * Set the glue (the string between the parts).
     *
     * @param string $glue
     *
     * @return self
     **/
    public function setGlue($glue)
    {
        $this->glue = $glue;

        return $this;
    }

    /**
     * Return the string prefix.
     *
     * @return string
     **/
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * Set the prefix.
     *
     * @param string $prefix
     *
     * @return self
     **/
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;

        return $this;
    }

    /**
     * Return the suffix.
     *
     * @return string
     **/
    public function getSuffix()
    {
        return $this->suffix;
    }

    /**
     * Set the suffix.
     *
     * @param string $suffix
     *
     * @return self
     **/
    public function setSuffix($suffix)
    {
        $this->suffix = $suffix;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param array|\Traversable|int|string $source (optional)
     *
     * @return self
     **/
    public function setSource($source)
    {
        if (!is_string($source)) {
            return parent::setSource($source);
        }

        if ($this->glue === '') {
            return parent::setSource(str_split($source));
        }

        if (!$length = mb_strlen($source)) {
            $this->source = [];

            return $this;
        }

        $this->source = explode($this->glue, trim($source, $this->glue));

        return $this;
    }

    /**
     * Test if this StringList is equal to a string, another  StringList.
     *
     * @param string|StringList $other
     *
     * @param bool $considerAffix (default=false)
     *
     * @return bool
     */
    public function equals($other, $considerAffix=false)
    {

        if ($other instanceof self) {
            return $considerAffix ? ($other->toString() == $this->toString())
                                  : ($other->getSource() == $this->source);
        }

        $other = "$other";

        if ($considerAffix) {
            return $other == $this->toString();
        }

        $cleaned = $other;

        if ($this->prefix && mb_strpos($other, $this->prefix) === 0) {
            $cleaned = mb_substr($other, mb_strlen($this->prefix));
        }

        if ($this->suffix && mb_strpos($other, $this->suffix)) {
            $cleaned = mb_substr($cleaned, 0, 0-mb_strlen($this->suffix));
        }

        if ($this->hasDifferentAffixes()){
            $cleaned = trim($cleaned, $this->glue);
        }

        $me = implode($this->glue, $this->source);

        return ($me == $other || $me == $cleaned);


    }
    /**
     * Copies the list or its extended class.
     * 
     * @return self
     */
    public function copy()
    {
        return parent::copy()->setGlue($this->glue)
                             ->setPrefix($this->prefix)
                             ->setSuffix($this->suffix);
    }

    /**
     * @return string
     **/
    public function toString()
    {
        if (!$this->prefix && !$this->suffix) {
            return implode($this->glue, $this->source);
        }

        $middle = implode($this->glue, $this->source);

        // If not empty
        if ($middle) {
            return $this->prefix.$middle.$this->suffix;
        }

        // If suffix, prefix and glue are equal, normalize the result
        if ($this->glue === $this->prefix && $this->glue === $this->suffix) {
            return $this->prefix;
        }
        return $this->prefix.$this->suffix;
    }

    /**
     * Returns true if no prefix or suffix setted or if one of them differes
     * from the glue.
     *
     * @return bool
     */
    protected function hasDifferentAffixes()
    {
        if ($this->prefix && $this->prefix != $this->glue) {
            return false;
        }

        if ($this->suffix && $this->suffix != $this->glue) {
            return false;
        }

        return true;
    }
}
