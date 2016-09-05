<?php

namespace Ems\Mail;


use DateTime;
use Countable;
use Ems\Contracts\Core\NotFound;
use Ems\Contracts\Mail\MailConfigProvider;
use Ems\Core\ResourceNotFoundException;


class MailConfigProviderChain implements MailConfigProvider, Countable
{

    /**
     * @var array
     **/
    protected $providers = [];

    /**
     * {@inheritdoc}
     *
     * @param string $resourceName
     * @param mixed $resourceId (optional)
     * @return \Ems\Contracts\Mail\MailConfig
     **/
    public function configFor($resourceName, $resourceId=null)
    {

        foreach ($this->providers as $provider) {

            try {
                // Throw NotFound even if other providers dont
                if (!$config = $provider->configFor($resourceName, $resourceId)) {
                    continue;
                }
                return $config;

            } catch (NotFound $e) {
            }


        }

        throw new ResourceNotFoundException("No provider found a config found for '$resourceName' and id '$resourceId'");

    }

    /**
     * Add a provider. Later added providers will be asked first
     *
     * @param \Ems\Contracts\Mail\MailConfigProvider
     * @return self
     **/
    public function add(MailConfigProvider $provider)
    {
        array_unshift($this->providers, $provider);
        return $this;
    }

    /**
     * Remove a provider
     *
     * @param \Ems\Contracts\Mail\MailConfigProvider
     * @return self
     **/
    public function remove(MailConfigProvider $provider)
    {
        $this->providers = array_filter($this->providers, function ($known) use ($provider) {
            return spl_object_hash($known) != spl_object_hash($provider);
        });
    }

    /**
     * Return the amount of assigned providers
     *
     * @return int
     **/
    public function count()
    {
        return count($this->providers);
    }
}
