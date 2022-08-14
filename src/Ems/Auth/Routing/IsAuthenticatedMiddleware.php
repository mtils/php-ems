<?php
/**
 *  * Created by mtils on 14.08.2022 at 07:23.
 **/

namespace Ems\Auth\Routing;

use Ems\Contracts\Auth\Auth;
use Ems\Contracts\Auth\LoggedOutException;
use Ems\Contracts\Routing\Input;

class IsAuthenticatedMiddleware
{
    /**
     * @var Auth
     */
    protected $auth;

    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    public function __invoke(Input $input, callable $next)
    {
        if (!$user = $input->getUser() ) {
            throw new LoggedOutException('No user was set at the request. This is an error.');
        }
        if (!$this->auth->isAuthenticated($user)) {
            throw new LoggedOutException('Nobody is logged in.');
        }
        return $next($input);
    }
}