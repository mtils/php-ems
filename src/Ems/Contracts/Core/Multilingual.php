<?php

namespace Ems\Contracts\Core;

/**
 * This is the translatable part of a text provider which not has to be a
 * translator.
 **/
interface Multilingual extends AssignedToLocale
{
    /**
     * Return a new instance of this provider for locale $locale.
     *
     * @param string       $locale
     * @param string|array $fallbacks (optional)
     *
     * @return self
     **/
    public function forLocale($locale, $fallbacks=null);

    /**
     * Set the current locale. Remember to apply this to all "forks" you built
     * via forDomain().
     *
     * @param string $locale
     *
     * @return self
     **/
    public function setLocale($locale);

    /**
     * Return the fallback locales.
     *
     * @return array
     */
    public function getFallbacks();

    /**
     * Set the locale fallback(s).
     *
     * @param string|array $fallback
     *
     * @return $this
     */
    public function setFallbacks($fallback);

}
