<?php

namespace Ems\Contracts\Core;

/**
 * The TextProvider is what you normally call a translator in an application. This
 * wording seems to be unusual but if you look closer it describes better what
 * you do in your application.
 * In Zend for example you call $tr->translate($message) where $message is
 * documented as a message id.
 * in Symfony you call $tr->trans($id) where $id is the id of a message
 * in Laravel you call $tra->get($id)
 * ...
 * So at the end you are not translating something. What you actually do is not
 * writing any texts in your code, you write an id and let some object turn
 * it into text. So this is more like a glossary or technically a TextProvider.
 *
 * I tried to find the right wording for translate|get|trans and realized there
 * is no translation happening. So the wording here is different.
 *
 * The multilingual features has been removed of this class, because the consumer
 * of this class usually does not care about a manual locale.
 **/
interface TextProvider
{
    /**
     * Return if the TextProvider has an entry for $key.
     *
     * @param string $key
     *
     * @return bool
     **/
    public function has($key);

    /**
     * Return the text for messageId $key. Replace the vars in it with $replace.
     *
     * @param string $key
     * @param array  $replace (optional)
     *
     * @return string
     **/
    public function get($key, array $replace = []);

    /**
     * Return the text for a choice depending on a number. Mostly used to get
     * different texts for different quantities.
     *
     * @param string $key
     * @param int    $number
     * @param array  $replace (optional)
     *
     * @return string
     **/
    public function choice($key, $number, array $replace = []);

    /**
     * Return the domain of this TextProvider.
     *
     * @return string
     **/
    public function getDomain();

    /**
     * Return a new instance of this TextProvider for text domain $domain.
     * Instead of not using this feature (like the most apps do) the (text)domain
     * is like an offset to the translations. So you can pass a TextProvider to
     * an object which has already an offset and the consuming class can use
     * shorter keys to its translations.
     * You could for example pass a $texts->forDomain('messages') to a controller
     * or a $texts->forDomain('forms') to a form object.
     * If the domain is not known an exception has to be thrown.
     *
     * @param string $domain
     *
     * @throws \Ems\Contracts\Core\Errors\NotFound
     *
     * @return self
     **/
    public function forDomain($domain);
}
