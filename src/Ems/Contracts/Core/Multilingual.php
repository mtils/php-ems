<?php

namespace Ems\Contracts\Core;

/**
 * This is the translatable part of a text provider which not has to be a
 * translator.
 **/
interface Multilingual
{
    /**
     * Return a new instance of this provider for locale $locale.
     *
     * @param string $locale
     *
     * @return self
     **/
    public function forLocale($locale);

    /**
     * Return the current locale.
     *
     * @return string
     **/
    public function getLocale();

    /**
     * Set the current locale. Remember to apply this to all "forks" you built
     * via forDomain().
     *
     * @param string $locale
     *
     * @return self
     **/
    public function setLocale($locale);
}
