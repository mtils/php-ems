<?php


namespace Ems\Mail;

use RuntimeException;
use Ems\Contracts\Mail\AddressExtractor;


class GuessingAddressExtractor implements AddressExtractor
{

    protected $possibleEmailMethods = [
        'getEmail',
        'getEmailForPasswordReset',
        'getAuthEmail'
    ];

    protected $possibleEmailProperties = [
        'email',
        'email2'
    ];

    protected $nameToEmailMap = [];

    /**
     * {@inheritdoc}
     *
     * @param mixed $contact
     * @return string The email address
     **/
    public function email($contact)
    {

        if ($this->isStringLike($contact) && $this->isMapped($contact)) {
            return $this->mappedEmail($contact);
        }

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

    public function isMapped($name)
    {
        return isset($this->nameToEmailMap["$name"]);
    }

    public function mappedEmail($name)
    {
        return isset($this->nameToEmailMap["$name"]) ? $this->nameToEmailMap["$name"] : '';
    }

    public function mapToEmail($name, $email)
    {
        $this->nameToEmailMap[$name] = $email;
        return $this;
    }

    protected function isStringLike($contact)
    {
        return (is_string($contact) || method_exists($contact, '__toString')) && !method_exists($contact, 'jsonSerialize');
    }

    protected function isEmailAddress($contact) {
        if ($contact instanceof Illuminate\Database\Eloquent\Model) {
            return false;
        }
        return (filter_var($contact, FILTER_VALIDATE_EMAIL) !== false);
    }

}
