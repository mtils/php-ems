<?php

namespace Ems;

use Ems\Assets\Skeleton\AssetsBootstrapper;
use Ems\Cache\Skeleton\CacheBootstrapper;
use Ems\Skeleton\Application;
use Ems\Core\IOCContainer;
use Ems\Skeleton\CoreBootstrapper;
use Ems\Routing\Skeleton\RoutingBootstrapper;
use Ems\Skeleton\BootManager;
use Ems\Skeleton\SkeletonBootstrapper;
use Ems\XType\Skeleton\XTypeBootstrapper;

/**
 * Trait AppTrait
 * @package Ems
 *
 * @property $extraBootstrappers
 */
trait AppTrait
{

    /**
     * @var IOCContainer
     */
    protected $_container;

    /**
     * @var Application
     **/
    protected $_app;

    /**
     * @param string|null $binding    (optional)
     * @param array       $parameters (optional)
     *
     * @return IOCContainer|object
     */
    public function container($binding = null, array $parameters=[])
    {
        if (!$this->_container) {
            $this->_container = new IOCContainer();
        }
        return $binding ? $this->_container->__invoke($binding, $parameters) : $this->_container;
    }

    /**
     * @param string $binding    (optional)
     * @param array  $parameters (optional)
     *
     * @return Application
     **/
    public function app($binding = null, array $parameters = [])
    {
        $app = $this->appInstance();

        if (!$app->wasBooted()) {
            $this->configureApplication($app);
            $this->bootApplication($app);
        }
        return $binding ? $app->__invoke($binding, $parameters) : $app;
    }

    /**
     * @return Application
     */
    protected function appInstance()
    {
        if (!$this->_app) {
            $this->_app = $this->createApplication(realpath(__DIR__.'/../../../'));
        }
        return $this->_app;
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
        $app = new Application($appPath, $this->container());

        $app->setVersion('0.1.9.4')
            ->setName('Integration Test Application');

        return $app;
    }

    /**
     * Overwrite this method to configure the application before booting.
     *
     * @param \Ems\Skeleton\Application $app
     */
    protected function configureApplication(Application $app)
    {
        //
    }

    /**
     * Boot add the bootstrappers and boot the application.
     *
     * @param Application $app
     **/
    protected function bootApplication(Application $app)
    {
        $bootManager = new BootManager($app->getContainer());
        $bootManager->setApplication($app);
        $this->addBootstrappers($bootManager);
        $app->boot();
        $this->boot($app);
    }

    /**
     * Overwrite this method for simple boot configurations
     *
     * @param Application $app
     * @return void
     */
    protected function boot(Application $app)
    {
        //
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

        $bootstrappers = [
            CoreBootstrapper::class,
            CacheBootstrapper::class,
            AssetsBootstrapper::class,
            XTypeBootstrapper::class,
            RoutingBootstrapper::class,
            SkeletonBootstrapper::class
        ];

        if (!isset($this->extraBootstrappers)) {
            return $bootstrappers;
        }
        foreach (($this->extraBootstrappers) as $bootstrapper) {
            $bootstrappers[] = $bootstrapper;
        }
        return $bootstrappers;
    }
}
