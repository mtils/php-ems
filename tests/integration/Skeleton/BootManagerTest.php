<?php

namespace Ems\Skeleton;
use Ems\Testing\LoggingCallable;
use Ems\Testing\Cheat;

class BootManagerTest extends \Ems\IntegrationTest
{
    public function test_it_calls_configurators_once_before_adding()
    {
        $manager = $this->newBootManager();
        $booter = new LoggingCallable();
        BootManager::configureBy($booter);

        $this->assertCount(0, $booter);
        $manager->add('stdClass');
        $this->assertCount(1, $booter);
        $manager->add('stdClass');
        $this->assertCount(1, $booter);
    }

    public function test_it_hooks_into_boot_process()
    {
        $manager = $this->newBootManager();
        $app = $this->app();

        $manager->setApplication($app);
        $this->assertSame($app, $manager->getApplication());

        $packageBinder = new LoggingCallable();
        $binder = new LoggingCallable();
        $booter = new LoggingCallable();
        $callables = [$packageBinder, $binder, $booter];

        $manager->add(BootManagerTestBooter::class);
        $manager->addPackageBinder('package', $packageBinder);
        $manager->addBinder('bind', $binder);
        $manager->addBooter('boot', $booter);

        $this->assertAllCount(0, $callables);

        $app->boot();

        $this->assertAllCount(1, $callables);

        $object = Cheat::call($manager, 'resolveOnce', [BootManagerTestBooter::class]);

        $this->assertEquals(1, $object->packageCalled);
        $this->assertEquals(1, $object->bindCalled);
        $this->assertEquals(1, $object->bootCalled);
    }

    public function test_it_boots_passed_object()
    {
        $manager = $this->newBootManager();
        $app = $this->app();

        $manager->setApplication($app);
        $this->assertSame($app, $manager->getApplication());


        $booter = new BootManagerTestBooter();
        $manager->add($booter);

        $app->boot();

        $this->assertEquals(1, $booter->packageCalled);
        $this->assertEquals(1, $booter->bindCalled);
        $this->assertEquals(1, $booter->bootCalled);

        $this->assertSame($booter, Cheat::call($manager, 'resolveOnce', [BootManagerTestBooter::class]));
    }

    protected function assertAllCount($count, $countables)
    {
        foreach ($countables as $countable) {
            $this->assertCount($count, $countable);
        }
    }

    protected function newBootManager()
    {
        return new BootManager($this->app());
    }
}

class BootManagerTestBooter
{
    public $packageCalled = 0;

    public $bindCalled = 0;

    public $bootCalled = 0;


    public function bindPackages()
    {
        $this->packageCalled++;
    }

    public function bind()
    {
        $this->bindCalled++;
    }

    public function boot()
    {
        $this->bootCalled++;
    }
}
