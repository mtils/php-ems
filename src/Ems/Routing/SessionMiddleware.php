<?php
/**
 *  * Created by mtils on 16.01.2022 at 09:17.
 **/

namespace Ems\Routing;

use Ems\Contracts\Http\Cookie;
use Ems\Contracts\Routing\Input;
use Ems\Http\HttpResponse;
use UnexpectedValueException;

use function get_class;

class SessionMiddleware
{
    /**
     * @var string
     */
    protected $cookieName = '';

    /**
     * @var array
     */
    protected $clientTypes = [Input::CLIENT_WEB, Input::CLIENT_AJAX, Input::CLIENT_CMS, Input::CLIENT_MOBILE];

    public function __construct()
    {

    }

    public function __invoke(Input $input, callable $next)
    {
        if (!$input instanceof HttpInput) {
            return $next($input);
        }

        $newRequest = $this->addSession($input);

        $response = $next($newRequest);

        if (!$response instanceof HttpResponse) {
            throw new UnexpectedValueException('The response to HttpInput should be HttpResponse not ' . get_class($response));
        }

        if (!$newRequest->session->isStarted()) {
            return $response;
        }

        if (!$this->hasSessionCookie($input)) {
            $response = $response->withCookie($this->createCookie($input, $newRequest->session->getId()));
        }

        $newRequest->session->persist();

        return $response;
    }

    public function createCookie(HttpInput $input, $sessionId) : Cookie
    {
        return new Cookie($this->cookieName, $sessionId);
    }

    /**
     * Get the client types that should have a session.
     *
     * @return string[]
     */
    public function getEnabledClientTypes() : array
    {
        return $this->clientTypes;
    }

    /**
     * Set the client types that should have a session.
     *
     * @param string[] $clientTypes
     * @return $this
     */
    public function setEnabledClientTypes(array $clientTypes) : SessionMiddleware
    {
        $this->clientTypes = $clientTypes;
        return $this;
    }

    /**
     * Check if the session would be started on the passed request.
     *
     * @param Input $input
     * @return bool
     */
    public function isEnabledInput(Input $input) : bool
    {
        if (!$input instanceof HttpInput) {
            return false;
        }
        return in_array($input->getClientType(), $this->clientTypes);
    }

    protected function addSession(HttpInput $input)
    {
        $session = $this->createSession();
        if ($this->hasSessionCookie($input)) {
            $session->setId($input->cookie[$this->cookieName]);
        }
        return $input->withSession($session);
    }

    protected function createSession() : Session
    {
        return new Session();
    }

    protected function hasSessionCookie(HttpInput $input) : bool
    {
        return isset($input->cookie[$this->cookieName]) && $input->cookie[$this->cookieName];
    }
}