<?php
/**
 *  * Created by mtils on 05.06.18 at 15:54.
 **/

namespace Ems\Core\Support;


use Ems\Contracts\Core\Multilingual;

/**
 * Trait MultilingualTrait
 *
 * This trait tries to make implementing multilingual classes more easy
 *
 * @see \Ems\Contracts\Core\Multilingual
 *
 * @package Ems\Core\Support
 */
trait MultilingualTrait
{
    /**
     * @var string
     **/
    protected $locale;

    /**
     * @var array
     */
    protected $localeFallbacks = [];

    /**
     * @var array
     */
    protected $localeSequence = [];

    /**
     * Return a new instance of this provider for locale $locale.
     *
     * @param string $locale
     * @param string|array $fallbacks (optional)
     *
     * @return Multilingual
     **/
    public function forLocale($locale, $fallbacks=null)
    {
        $fork = $this->replicate()->setLocale($locale);

        if ($fallbacks) {
            return $fork->setFallbacks($fallbacks);
        }

        if ($this->localeFallbacks) {
            return $fork->setFallbacks($this->localeFallbacks);
        }

        return $fork;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     **/
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $locale
     *
     * @return self
     **/
    public function setLocale($locale)
    {
        $this->locale = $locale;
        $this->localeSequence = [];
        return $this;
    }

    /**
     * Return the fallback locales.
     *
     * @return array
     */
    public function getFallbacks()
    {
        return $this->localeFallbacks;
    }

    /**
     * Set the locale fallback(s).
     *
     * @param string|array $fallback
     *
     * @return Multilingual
     */
    public function setFallbacks($fallback)
    {
        $this->localeFallbacks = (array)$fallback;
        $this->localeSequence = [];
        return $this;
    }

    /**
     * Returns a new instance of this Multilingual.
     *
     * @param array $properties
     *
     * @return Multilingual
     **/
    protected function replicate(array $properties=[])
    {
        $class = get_class($this);
        return new $class();
    }

    /**
     * Calculates the priority for loading formatting keys. (e.g. de_DE, de, en)
     *
     * @return array
     */
    protected function localeSequence()
    {
        if ($this->localeSequence) {
            return $this->localeSequence;
        }

        if ($this->localeFallbacks) {
            $this->localeSequence = $this->localeFallbacks;
        }

        if (!$this->locale) {
            return $this->localeSequence;
        }

        if (!strpos($this->locale, '_') ) {
            array_unshift($this->localeSequence, $this->locale);
            return $this->localeSequence;
        }

        $base = explode('_', $this->locale, 2)[0];

        array_unshift($this->localeSequence, $base);
        array_unshift($this->localeSequence, $this->locale);

        return $this->localeSequence;
    }
}