<?php

namespace Ems\Contracts\Core;

/**
 * This interface is one of the fundamentals of this framework.
 * This fundamental is: "Care at this point of code about what this point is caring about"
 * So for example a controller should not deceide everything concerning the action it processes.
 * For example a web page registration. Much things are happening during a registration:
 * Activation codes, tokens, emails, templates, messages,....
 * To allow to defer this things to other places I introduced that defined endpoints for your
 * application actions are defined like in REST and all the (service) objects are now acting
 * based on that resource name. (Mail::mailFor('registration.create'))
 * So the control is outside of the controller.
 **/
interface AppliesToResource
{
    /**
     * Return the resource name. A resource name is a locator for your
     * application endpoint. Its very similar to a REST endpoint.
     * A resource name is a resource endpoint with an action
     * with dots instead of (url) slashes.
     * For example: 'users.index' (url would be /users),
     * 'mail.attachments.create' (url would be /mail/136/attachments/create).
     *
     * @return string
     **/
    public function resourceName();
}
