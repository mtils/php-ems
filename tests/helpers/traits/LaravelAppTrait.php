<?php

namespace Ems;

use Ems\Assets\Skeleton\AssetsBootstrapper;
use Ems\Cache\Skeleton\CacheBootstrapper;
use Ems\Core\Laravel\IOCContainer;
use Ems\Core\Skeleton\CoreBootstrapper;
use Ems\XType\Skeleton\XTypeBootstrapper;
use Ems\Skeleton\Application;
use Ems\Skeleton\BootManager;
use Illuminate\Contracts\Container\Container as Laravel;

trait LaravelAppTrait
{
    /**
     * @var \Ems\Skeleton\Application
     **/
    protected $_app;

    /**
     * @var
     **/
    protected $_laravel;

    /**
     * @param string $binding    (optional)
     * @param array  $parameters (optional)
     *
     * @return \Ems\Skeleton\Application|mixed
     **/
    public function app($binding = null, array $parameters = [])
    {
        if (!$this->_app) {
            $this->_app = $this->createApplication(realpath(__DIR__.'/../../../'));
            $this->bootApplication($this->_app);
        }

        return $binding ? $this->_app->__invoke($binding, $parameters) : $this->_app;
    }

    /**
     * @param string $binding    (optional)
     * @param array  $parameters (optional)
     *
     * @return Illuminate\Contracts\Container\Container
     **/
    public function laravel($binding = null, array $parameters = [])
    {
        $laravel = $this->app()->getContainer()->laravel();
        return $binding ? $laravel->make($binding, $parameters) : $laravel;
    }

    /**
     * Create the application and return it.
     *
     * @param string $appPath
     *
     * @return \Ems\Skeleton\Application
     **/
    protected function createApplication($appPath)
    {
        $app = new Application($appPath, new IOCContainer());

        $app->setVersion('0.1.9.4')
            ->setName('Integration Test Application');

        return $app;
    }

    /**
     * Boot add the bootstrappers and boot the application.
     *
     * @param \Ems\Skeleton\Application $app
     **/
    protected function bootApplication(Application $app)
    {
        $this->registerCoreContainerAliases($app->getContainer()->laravel());
        $this->addServiceProviders($app->getContainer()->laravel());
        $app->boot();
    }

    /**
     * Add the ServiceProviders to the bootmanager.
     *
     * @param Laravel $container
     */
    protected function addServiceProviders(Laravel $container)
    {

        $providers = [];

        foreach ($this->serviceProviders() as $serviceProviderClass) {

            $providers[] = new $serviceProviderClass($container);

        }

        foreach ($providers as $provider) {
            if (!method_exists($provider, 'register')) {
                continue;
            }
            $container->call([$provider, 'register']);
        }

        foreach ($providers as $provider) {
            if (!method_exists($provider, 'boot')) {
                continue;
            }
            $container->call([$provider, 'boot']);
        }
    }

    protected function registerCoreContainerAliases(Laravel $container)
    {
        $aliases = [
            'app'                  => ['Illuminate\Foundation\Application', 'Illuminate\Contracts\Container\Container', 'Illuminate\Contracts\Foundation\Application'],
            'auth'                 => 'Illuminate\Auth\AuthManager',
            'auth.driver'          => ['Illuminate\Auth\Guard', 'Illuminate\Contracts\Auth\Guard'],
            'auth.password.tokens' => 'Illuminate\Auth\Passwords\TokenRepositoryInterface',
            'blade.compiler'       => 'Illuminate\View\Compilers\BladeCompiler',
            'cache'                => ['Illuminate\Cache\CacheManager', 'Illuminate\Contracts\Cache\Factory'],
            'cache.store'          => ['Illuminate\Cache\Repository', 'Illuminate\Contracts\Cache\Repository'],
            'config'               => ['Illuminate\Config\Repository', 'Illuminate\Contracts\Config\Repository'],
            'cookie'               => ['Illuminate\Cookie\CookieJar', 'Illuminate\Contracts\Cookie\Factory', 'Illuminate\Contracts\Cookie\QueueingFactory'],
            'encrypter'            => ['Illuminate\Encryption\Encrypter', 'Illuminate\Contracts\Encryption\Encrypter'],
            'db'                   => 'Illuminate\Database\DatabaseManager',
            'db.connection'        => ['Illuminate\Database\Connection', 'Illuminate\Database\ConnectionInterface'],
            'events'               => ['Illuminate\Events\Dispatcher', 'Illuminate\Contracts\Events\Dispatcher'],
            'files'                => 'Illuminate\Filesystem\Filesystem',
            'filesystem'           => ['Illuminate\Filesystem\FilesystemManager', 'Illuminate\Contracts\Filesystem\Factory'],
            'filesystem.disk'      => 'Illuminate\Contracts\Filesystem\Filesystem',
            'filesystem.cloud'     => 'Illuminate\Contracts\Filesystem\Cloud',
            'hash'                 => 'Illuminate\Contracts\Hashing\Hasher',
            'translator'           => ['Illuminate\Translation\Translator', 'Symfony\Component\Translation\TranslatorInterface'],
            'log'                  => ['Illuminate\Log\Writer', 'Illuminate\Contracts\Logging\Log', 'Psr\Log\LoggerInterface'],
            'mailer'               => ['Illuminate\Mail\Mailer', 'Illuminate\Contracts\Mail\Mailer', 'Illuminate\Contracts\Mail\MailQueue'],
            'auth.password'        => ['Illuminate\Auth\Passwords\PasswordBroker', 'Illuminate\Contracts\Auth\PasswordBroker'],
            'queue'                => ['Illuminate\Queue\QueueManager', 'Illuminate\Contracts\Queue\Factory', 'Illuminate\Contracts\Queue\Monitor'],
            'queue.connection'     => 'Illuminate\Contracts\Queue\Queue',
            'redirect'             => 'Illuminate\Routing\Redirector',
            'redis'                => ['Illuminate\Redis\Database', 'Illuminate\Contracts\Redis\Database'],
            'request'              => 'Illuminate\Http\Request',
            'router'               => ['Illuminate\Routing\Router', 'Illuminate\Contracts\Routing\Registrar'],
            'session'              => 'Illuminate\Session\SessionManager',
            'session.store'        => ['Illuminate\Session\Store', 'Symfony\Component\HttpFoundation\Session\SessionInterface'],
            'url'                  => ['Illuminate\Routing\UrlGenerator', 'Illuminate\Contracts\Routing\UrlGenerator'],
            'validator'            => ['Illuminate\Validation\Factory', 'Illuminate\Contracts\Validation\Factory'],
            'view'                 => ['Illuminate\View\Factory', 'Illuminate\Contracts\View\Factory'],
        ];

        foreach ($aliases as $key => $aliases) {
            foreach ((array) $aliases as $alias) {
                $container->alias($key, $alias);
            }
        }
    }

    /**
     * Return all the service providers this test needs. Defaults to all.
     * Assign an array of class names named $this->serviceProviders to
     * change the service providers.
     *
     * @return array
     **/
    protected function serviceProviders()
    {
        if (isset($this->serviceProviders)) {
            return $this->serviceProviders;
        }

        return [
            \Illuminate\Events\EventServiceProvider::class,
            //\Illuminate\Database\DatabaseServiceProvider::class, (uses config)
            \Illuminate\Filesystem\FilesystemServiceProvider::class,
            // \Illuminate\Mail\MailServiceProvider::class (uses Application methods, not only container)
//             \Illuminate\Translation\TranslationServiceProvider::class, (uses config, use the one below)
            \Ems\Testing\Illuminate\NullTranslationServiceProvider::class,
            \Illuminate\Validation\ValidationServiceProvider::class,
            \Illuminate\View\ViewServiceProvider::class,
            \Ems\Core\Laravel\CoreServiceProvider::class,
            \Ems\XType\Illuminate\XTypeServiceProvider::class,
            \Ems\Validation\Illuminate\EmsValidationServiceProvider::class,
            \Ems\Foundation\Illuminate\FoundationServiceProvider::class
        ];
    }
}
