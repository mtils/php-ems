<?php
/**
 *  * Created by mtils on 16.08.2022 at 07:24.
 **/

namespace Ems\Auth\Routing;

use Ems\Contracts\Auth\Auth;
use Ems\Contracts\Auth\Exceptions\LoggedOutException;
use Ems\Contracts\Auth\Exceptions\NotAllowedException;
use Ems\Contracts\Routing\Input;

class IsAllowedMiddleware
{
    /**
     * @var Auth
     */
    protected $auth;

    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    public function __invoke(Input $input, callable $next, string $resource, string $operation='')
    {
        if (!$user = $input->getUser() ) {
            throw new LoggedOutException('No user was set at the request. This is an error.');
        }
        $operation = $operation ?: Auth::ACCESS;
        if (!$this->auth->allowed($user, $resource, $operation)) {
            throw new NotAllowedException("The current user is now allowed to access $resource:$operation");
        }
        return $next($input);
    }
}