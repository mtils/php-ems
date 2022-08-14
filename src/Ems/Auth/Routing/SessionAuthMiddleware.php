<?php
/**
 *  * Created by mtils on 19.07.2022 at 21:44.
 **/

namespace Ems\Auth\Routing;

use Ems\Contracts\Auth\Auth;
use Ems\Contracts\Routing\Input;
use Ems\Contracts\Routing\Session;
use Ems\Routing\ArgvInput;
use Ems\Routing\HttpInput;

class SessionAuthMiddleware
{
    /**
     * @var Auth
     */
    protected $auth;

    protected $sessionKey = 'ems-auth-id';

    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    public function __invoke(Input $input, callable $next)
    {
        if ($input instanceof ArgvInput) {
            $user = $this->auth->specialUser(Auth::SYSTEM);
            return $next($input->withUser($user));
        }
        if (!$input instanceof HttpInput || !isset($input->session[$this->sessionKey])) {
             return $next($input->withUser($this->auth->specialUser(Auth::GUEST)));
        }
        $user = $this->auth->userByCredentials($input->session[$this->sessionKey]);
        return $next($input->withUser($user));
    }

    /**
     * @return string
     */
    public function getSessionKey(): string
    {
        return $this->sessionKey;
    }

    /**
     * @param string $sessionKey
     */
    public function setSessionKey(string $sessionKey): void
    {
        $this->sessionKey = $sessionKey;
    }

    public function persistInSession(array $credentials, Session $session)
    {
        $session[$this->sessionKey] = $credentials;
    }

    public function removeFromSession(Session $session) : bool
    {
        if (isset($session[$this->sessionKey])) {
            unset($session[$this->sessionKey]);
            return true;
        }
        return false;
    }
}