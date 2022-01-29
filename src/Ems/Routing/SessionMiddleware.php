<?php
/**
 *  * Created by mtils on 16.01.2022 at 09:17.
 **/

namespace Ems\Routing;

use Ems\Contracts\Core\Extendable;
use Ems\Contracts\Http\Cookie;
use Ems\Contracts\Routing\Input;
use Ems\Core\Patterns\ExtendableTrait;
use Ems\Http\HttpResponse;
use Ems\Routing\SessionHandler\ArraySessionHandler;
use SessionHandler;
use UnexpectedValueException;

use function get_class;
use function session_get_cookie_params;
use function session_name;

class SessionMiddleware implements Extendable
{
    use ExtendableTrait;

    public const COOKIE_NAME = 'name';

    public const DRIVER_ARRAY = 'array';

    public const DRIVER_NATIVE = 'native';

    /**
     * @var string
     */
    protected $driver = self::DRIVER_NATIVE;

    /**
     * @var int
     */
    protected $lifeTime = 120;

    /**
     * @var array
     */
    protected $cookieConfig = [];

    public function __construct()
    {
        $this->addDefaultDrivers();
    }

    public function __invoke(Input $input, callable $next)
    {
        if (!$this->shouldHaveSession($input)) {
            return $next($input);
        }

        /** @var HttpInput $input */
        $newRequest = $this->addSession($input);

        $response = $next($newRequest);

        if (!$response instanceof HttpResponse) {
            throw new UnexpectedValueException('The response to HttpInput should be HttpResponse not ' . get_class($response));
        }

        if (!$newRequest->session->isStarted()) {
            return $response;
        }

        if (!$this->hasCookie($input, $this->getCookieName())) {
            $response = $response->withCookie(
                $this->createCookie($this->getCookieConfig(), $newRequest->session->getId(), $input)
            );
        }

        $newRequest->session->persist();

        return $response;
    }

    /**
     * @param array $config
     * @param string $sessionId
     * @param HttpInput $input
     * @return Cookie
     */
    protected function createCookie(array $config, string $sessionId, HttpInput $input) : Cookie
    {
        return new Cookie(
            $config[self::COOKIE_NAME],
            $sessionId,
            isset($config['lifetime']) ? ($config['lifetime'] =='session' ? 0 : (int)$config['lifetime']) : $this->lifeTime,
            $config['path'] ?? null,
            $config['domain'] ?? null,
            $config['secure'] ?? null,
            $config['httponly'] ?? null,
            $config['samesite'] ?? null
        );
    }

    /**
     * Check if the session would be started on the passed request.
     *
     * @param Input $input
     * @return bool
     */
    public function shouldHaveSession(Input $input) : bool
    {
        return $input instanceof HttpInput;
    }

    /**
     * @return string
     */
    public function getCookieName() : string
    {
        if (isset($this->cookieConfig[self::COOKIE_NAME]) && $this->cookieConfig[self::COOKIE_NAME]) {
            return $this->cookieConfig[self::COOKIE_NAME];
        }
        return session_name();
    }

    /**
     * The session handler
     *
     * @return string
     */
    public function getDriver(): string
    {
        return $this->driver;
    }

    /**
     * @param string $driver
     * @return SessionMiddleware
     */
    public function setDriver(string $driver): SessionMiddleware
    {
        $this->driver = $driver;
        return $this;
    }

    /**
     * @return int
     */
    public function getLifeTime() : int
    {
        return $this->lifeTime;
    }

    /**
     * @param int $lifeTime
     * @return SessionMiddleware
     */
    public function setLifeTime(int $lifeTime) : SessionMiddleware
    {
        $this->lifeTime = $lifeTime;
        return $this;
    }

    /**
     * @return array
     */
    public function getCookieConfig(): array
    {
        if ($this->cookieConfig) {
            return $this->cookieConfig;
        }
        $this->cookieConfig = session_get_cookie_params();
        $this->cookieConfig[self::COOKIE_NAME] = session_name();
        return $this->cookieConfig;
    }

    /**
     * @param array $cookieConfig
     * @return SessionMiddleware
     */
    public function setCookieConfig(array $cookieConfig): SessionMiddleware
    {
        $config = $this->getCookieConfig();
        foreach ($cookieConfig as $key=>$value) {
            $config[$key] = $value;
        }
        $this->cookieConfig = $config;
        return $this;
    }

    protected function addSession(HttpInput $input) : HttpInput
    {
        $session = $this->createSession();
        $cookieName = $this->getCookieName();
        if ($this->hasCookie($input, $cookieName)) {
            $session->setId($input->cookie[$cookieName]);
        }
        return $input->withSession($session);
    }

    /**
     * @return Session
     */
    protected function createSession() : Session
    {
        $handler = $this->callExtension($this->getDriver(), [$this]);
        return new Session($handler);
    }

    protected function hasCookie(HttpInput $input, string $name) : bool
    {
        $name = $this->getCookieName();
        return isset($input->cookie[$name]) && $input->cookie[$name];
    }

    protected function addDefaultDrivers()
    {
        $this->extend(self::DRIVER_NATIVE, function () {
            return new SessionHandler();
        });
        $this->extend(self::DRIVER_ARRAY, function () {
            $data = [];
            return (new ArraySessionHandler($data))->setLifeTime($this->lifeTime);
        });
    }
}