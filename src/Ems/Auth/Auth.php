<?php
/**
 *  * Created by mtils on 16.07.2022 at 08:22.
 **/

namespace Ems\Auth;

use Ems\Contracts\Auth\Auth as AuthInterface;
use LogicException;

use function call_user_func;

class Auth implements AuthInterface
{

    /**
     * @var callable[]
     */
    protected $checkers = [];

    /**
     * @var array
     */
    protected $userData = [];

    /**
     * @var callable
     */
    protected $userProvider;

    /**
     * @param callable|null $userProvider
     */
    public function __construct(callable $userProvider)
    {
        $this->userProvider = $userProvider;
    }

    /**
     * @inheritDoc}
     *
     * @param array $credentials
     * @return object|null
     */
    public function userByCredentials(array $credentials): ?object
    {
        return call_user_func($this->userProvider, $credentials);
    }

    /**
     * {@inheritDoc}
     *
     * @param string $when
     * @return object
     */
    public function specialUser(string $when): object
    {
        return $this->userByCredentials($this->getCredentialsForSpecialUser($when));
    }

    /**
     * {@inheritDoc}
     *
     * @param object $user
     * @param string $when
     * @return bool
     */
    public function is(object $user, string $when): bool
    {
        if ($when !== AuthInterface::USER) {
            return $this->hasCredentials($user, $this->getCredentialsForSpecialUser($when));
        }
        foreach ($this->userData as $when=>$credentials) {
            if ($this->hasCredentials($user, $credentials)) {
                return false;
            }
        }
        return true;
    }

    /**
     * {@inheritDoc}
     *
     * @param object $user
     *
     * @return bool
     */
    public function isAuthenticated(object $user): bool
    {
        $credentials = $this->getCredentialsForSpecialUser(self::GUEST);
        return !$this->hasCredentials($user, $credentials);
    }

    /**
     * {@inheritDoc}
     *
     * @param object    $subject
     * @param mixed     $resource
     * @param string    $operation (default: access)
     *
     * @return bool
     */
    public function allowed(object $subject, $resource, string $operation = AuthInterface::ACCESS): bool
    {
        foreach ($this->checkers as $checker) {
            $result = $checker($subject, $resource, $operation);
            if (is_bool($result)) {
                return $result;
            }
        }
        return false;
    }


    /**
     * Add a "allowed" checker. Assign a callable that will be called with all
     * arguments of allowed.
     * Return null if your callable does not know if this is allowed or not. Then
     * the next callable will be asked.
     *
     * @param callable $checker
     * @return $this
     */
    public function addChecker(callable $checker) : self
    {
        $this->checkers[] = $checker;
        return $this;
    }

    /**
     * Get the credentials for special users like guest/nobody or cron/console
     *
     * @param string $when
     * @return array
     */
    public function getCredentialsForSpecialUser(string $when) : array
    {
        return $this->userData[$when];
    }

    /**
     * Set the credentials for a special user like guest or system
     *
     * @param string $when
     * @param array $credentials
     * @return void
     */
    public function setCredentialsForSpecialUser(string $when, array $credentials)
    {
        $this->userData[$when] = $credentials;
    }

    /**
     * Check if the user has the passt
     * @param object $user
     * @param array $credentials
     * @return bool
     */
    protected function hasCredentials(object $user, array $credentials) : bool
    {
        if (!$credentials) {
            throw new LogicException('Credentials cannot be empty when checking for em');
        }
        foreach ($credentials as $key=>$value) {
            if (!isset($user->$key)) {
                return false;
            }
            if ($user->$key !== $value) {
                return false;
            }
        }
        return true;
    }
}