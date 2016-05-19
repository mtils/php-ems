<?php


namespace Ems\Mail;

use RuntimeException;
use Ems\Contracts\Mail\AddressExtractor;


class GuessingAddressExtractor implements AddressExtractor
{

    $possibleEmailMethods = [
        'getEmail',
        'getEmailForPasswordReset',
        'getAuthEmail'
    ];

    $possibleEmailProperties = [
        'email',
        'email2'
    ];

    /**
     * {@inheritdoc}
     *
     * @param mixed $contact
     * @return string The email address
     **/
    public function email($contact)
    {

        if ($this->isStringLike($contact) && $this->isEmailAddress($contact)) {
            return $contact;
        }

        if (!is_object($contact)) {
            throw new RuntimeException('No idea how to extract an email address of ' . gettype($contact));
        }

        foreach ($this->possibleEmailMethods as $method) {
            if (method_exists($contact, $method)) {
                return $contact->{$method}();
            }
        }

        foreach ($this->possibleEmailProperties as $property) {
            if (isset($contact->{$property})) {
                return $contact->{$property};
            }
        }

        throw new RuntimeException('No idea how to extract an email address of ' . get_class($contact));
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $contact
     * @return string|null
     **/
    public function name($contact)
    {
    }

    protected function isStringLike($contact)
    {
        return (is_string($contact) || method_exists($contact, '__toString'));
    }

    protected function isEmailAddress($contact) {
        return (filter_var($value, FILTER_VALIDATE_EMAIL) !== false);
    }

}