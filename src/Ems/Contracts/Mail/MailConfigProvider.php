<?php


namespace Ems\Contracts\Mail;

/**
 * The MailConfigBuilder provides a MailConfig object for a resource.
 *
 * A simple example would be an registration email for activation, the
 * resourceName could be "registrations.create". So you have a distinct
 * MailConfig (sender, template,...) for this resource. If you had more than
 * one registration page (different countries for example) you would pass the
 * page id as the second parammeter
 **/
interface MailConfigProvider
{
    /**
     * Return a config for $resourceName and optional $resourceId which is the
     * primary key of $resource. Normally you have a global config for all
     * resources of $resourceName but sometimes you need different and therefore
     * you have an optional $resourceId.
     *
     * @param string $resourceName
     * @param mixed  $resourceId   (optional)
     *
     * @return \Ems\Contracts\Mail\MailConfig
     *
     * @throws \Ems\Contracts\Core\Errors\NotFound
     **/
    public function configFor($resourceName, $resourceId = null);
}
