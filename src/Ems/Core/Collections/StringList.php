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
            return parent::setSource(str_split($string));
        }

        if (!$length = mb_strlen($string)) {
            $this->source = [];

            return $this;
        }

        $this->source = explode($this->glue, trim($string, $this->glue));

        return $this;
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
    protected function renderString()
    {
        return $this->prefix.implode($this->glue, $this->source).$this->suffix;
    }
}
