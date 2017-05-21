<?php

namespace Ems;

use Ems\Assets\Skeleton\AssetsBootstrapper;
use Ems\Cache\Skeleton\CacheBootstrapper;
use Ems\Core\IOCContainer;
use Ems\Core\Skeleton\CoreBootstrapper;
use Ems\XType\Skeleton\XTypeBootstrapper;
use Ems\Skeleton\Application;
use Ems\Skeleton\BootManager;

trait AppTrait
{
    /**
     * @var Application
     **/
    protected $_app;

    /**
     * @param string $binding    (optional)
     * @param array  $parameters (optional)
     *
     * @return Application
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
     * Create the application and return it.
     *
     * @param string $appPath
     *
     * @return Application
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
     * @param Application $app
     **/
    protected function bootApplication(Application $app)
    {
        $this->addBootstrappers($app->bootManager());
        $app->boot();
    }

    /**
     * Add the bootstrappers to the bootmanager.
     *
     * @param BootManager $bootManager
     */
    protected function addBootstrappers(BootManager $bootManager)
    {
        foreach ($this->bootstrappers() as $bootstrapper) {
            $bootManager->add($bootstrapper);
        }
    }

    /**
     * Return all the bootstrappers this test needs. Defaults to all.
     * Assign an array of class names named $this->bootstrappers to
     * change the bootstrappers.
     *
     * @return array
     **/
    protected function bootstrappers()
    {
        if (isset($this->bootstrappers)) {
            return $this->bootstrappers;
        }

        return [
            CoreBootstrapper::class,
            CacheBootstrapper::class,
            AssetsBootstrapper::class,
            XTypeBootstrapper::class,
        ];
    }
}
