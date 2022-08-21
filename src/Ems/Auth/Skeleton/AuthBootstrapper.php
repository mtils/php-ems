<?php
/**
 *  * Created by mtils on 21.08.2022 at 08:07.
 **/

namespace Ems\Auth\Skeleton;

use Ems\Auth\Auth;
use Ems\Auth\Routing\IsAllowedMiddleware;
use Ems\Auth\Routing\IsAuthenticatedMiddleware;
use Ems\Auth\Routing\SessionAuthMiddleware;
use Ems\Contracts\Auth\Auth as AuthInterface;
use Ems\Contracts\Core\IOCContainer;
use Ems\Contracts\Routing\MiddlewareCollection as MiddlewareCollectionContract;
use Ems\Routing\MiddlewareCollection;
use Ems\Skeleton\Bootstrapper;

class AuthBootstrapper extends Bootstrapper
{
    protected $defaultConfig = [
        'nobody' => 'nobody@example.com',
        'system' => 'system@example.com',
    ];

    public function bind()
    {
        parent::bind();
        $this->container->bind(AuthInterface::class, function (IOCContainer $app) {
            $auth = $app->create(Auth::class);
            $config = $this->getConfig('auth');
            $auth->setCredentialsForSpecialUser(AuthInterface::GUEST, ['email' => $config['nobody']]);
            $auth->setCredentialsForSpecialUser(AuthInterface::SYSTEM, ['email' => $config['system']]);
            return $auth;
        }, true);
    }

    protected function addMiddleware(MiddlewareCollectionContract $middlewares)
    {
        MiddlewareCollection::alias('auth', IsAuthenticatedMiddleware::class);
        MiddlewareCollection::alias('allowed', IsAllowedMiddleware::class);
        $middlewares->add('session-auth', SessionAuthMiddleware::class)->after('session');
    }

}