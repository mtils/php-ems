<?php

namespace Ems;

use Ems\Assets\Skeleton\AssetsBootstrapper;
use Ems\Cache\Skeleton\CacheBootstrapper;
use Ems\Core\IOCContainer;
use Ems\Core\Skeleton\CoreBootstrapper;
use Ems\XType\Skeleton\XTypeBootstrapper;
use Ems\Core\Application;
use Ems\Skeleton\BootManager;

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
     * @param string $binding    (optional)
     * @param array  $parameters (optional)
     *
     * @return IOCContainer
     */
    public function container($binding = null, array $parameters=[])
    {
        if (!$this->_container) {
            $this->_container = new IOCContainer();
        }
        return $binding ? $this->_container->make($binding, $parameters) : $this->_container;
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
     * @return Application
     **/
    protected function createApplication($appPath)
    {
        $app = new Application($appPath, $this->container());

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
        $bootManager = new BootManager($app->getContainer());
        $bootManager->setApplication($app);
        $this->addBootstrappers($bootManager);
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
