<?php
/**
 *  * Created by mtils on 21.11.2021 at 08:10.
 **/

namespace Ems\Testing\Skeleton;

use Ems\Assets\Skeleton\AssetsBootstrapper;
use Ems\Cache\Skeleton\CacheBootstrapper;
use Ems\Contracts\Core\IOCContainer;
use Ems\Skeleton\Application;

use Ems\Core\Helper;
use Ems\Core\Skeleton\CoreBootstrapper;
use Ems\Routing\Skeleton\RoutingBootstrapper;
use Ems\Skeleton\BootManager;

use Ems\Skeleton\SkeletonBootstrapper;
use Ems\XType\Skeleton\XTypeBootstrapper;

use function get_class_methods;
use function realpath;

trait AppInTest
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * @var IOCContainer
     */
    protected $container;

    /**
     * Get the app or resolve a binding.
     *
     * @param null $binding
     * @param array $parameters
     * @return Application|object
     */
    protected function app($binding=null, array $parameters=[])
    {
        $app = $this->getAppInstance();
        if (!$app->wasBooted()) {
            $this->configureApplication($app);
            $this->bootApplication($app);
        }
        if (!$binding) {
            return $app;
        }
        return $parameters ? $app->create($binding, $parameters) : $app->get($binding);
    }

    /**
     * Overwrite this method to apply custom config variables before booting the
     * application.
     */
    protected function configureApplication(Application $app)
    {
        //
    }

    /**
     * Overwrite this to manipulate app before booting or how its booted.
     *
     * @param Application $app
     */
    protected function bootApplication(Application $app)
    {
        $app->onBefore('boot', function () use ($app) {
            $this->callAllBootMethods('beforeBoot', $app);
        });
        $app->onAfter('boot', function () use ($app) {
            $this->callAllBootMethods('afterBoot', $app);
        });

        if ($app->getContainer()->resolved(BootManager::class)) {
            $app->boot();
            return;
        }

        /** @var BootManager $boot */
        $boot = $app->create(BootManager::class);
        $boot->setApplication($app);
        foreach ($this->bootstrappers() as $bootstrapper) {
            $boot->add($bootstrapper);
        }
        $app->boot();

    }

    /**
     * @return Application
     */
    protected function getAppInstance() : Application
    {
        if (!$this->app) {
            $this->app = $this->createApplication();
        }
        return $this->app;
    }

    /**
     * Creates the application. Overwrite this in your test to create the real
     * application.
     *
     * @return Application
     */
    protected function createApplication() : Application
    {
        $dir = realpath(__DIR__.'/../../../../');
        $app = new Application($dir);
        $app->setVersion('0.1.9.4')
            ->setName('Integration Test Application');
        return $app;
    }

    /**
     * Calls all methods with $prefix and pass the application.
     *
     * @param string $prefix
     * @param Application $app
     */
    protected function callAllBootMethods(string $prefix, Application $app)
    {
        $methods = get_class_methods($this);
        foreach ($methods as $method) {
            if (!Helper::startsWith($method, $prefix)) {
                continue;
            }
            $this->$method($app);
        }
    }

    /**
     * Return all the bootstrappers this test needs. Defaults to all.
     * Assign an array of class names named $this->bootstrappers to
     * change the bootstrappers.
     * Applies only if not the standard app.php file was included.
     *
     * @return string[]
     **/
    protected function bootstrappers() : array
    {
        if (isset($this->bootstrappers)) {
            return $this->bootstrappers;
        }

        return [
            CoreBootstrapper::class,
            CacheBootstrapper::class,
            AssetsBootstrapper::class,
            XTypeBootstrapper::class,
            RoutingBootstrapper::class,
            SkeletonBootstrapper::class
        ];

    }
}