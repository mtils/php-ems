<?php

namespace Ems\Skeleton;

use Ems\Contracts\Core\IOCContainer;
use Ems\Core\Url;

class ApplicationTest extends \Ems\TestCase
{
    public function test_it_instanciates()
    {
        $this->assertInstanceOf(
            Application::class,
            $this->newApp()
        );
    }

    public function test_name_and_setName()
    {
        $appName = 'Incredible app';
        $app = $this->newApp();
        $this->assertSame($app, $app->setName($appName));
        $this->assertSame($appName, $app->name());
    }

    public function test_version_and_setVersion()
    {
        $version = '0.1.2';
        $app = $this->newApp();
        $this->assertSame($app, $app->setVersion($version));
        $this->assertSame($version, $app->version());
    }

    public function test_path_returns_passed_path()
    {
        $app = $this->newApp('/var/www/my-app');
        $this->assertEquals('/var/www/my-app', $app->path());
    }

    public function test_getPaths_and_setPaths()
    {
        $app = $this->newApp();
        $paths = [
            'assets'    => 'public/theme',
            'uploads'   => 'public/uploads',
            'config'    => 'config',
            'resources' => 'resources'
        ];

        $this->assertSame($app, $app->setPaths($paths));
        $this->assertEquals($paths, $app->getPaths());
    }

    public function test_path_returns_app_path()
    {
        $appPath = '/var/www/my-app';
        $app = $this->newApp($appPath);

        $this->assertEquals($appPath, $app->path());

        foreach (['/', '.', 'root', 'app'] as $key) {
            $this->assertEquals($appPath, $app->path($key));
        }

    }

    public function test_path_returns_assigned_paths()
    {
        $app = $this->newApp();
        $root = $app->path();
        $paths = [
            'assets'    => "$root/public/theme",
            'uploads'   => "$root/public/uploads",
            'config'    => "$root/config",
            'resources' => "$root/resources"
        ];

        $this->assertSame($app, $app->setPaths($paths));

        foreach ($paths as $key=>$path) {
            $url = $app->path($key);
            $this->assertEquals($path, $url);
        }

    }

    public function test_path_builds_paths_from_root()
    {
        $app = $this->newApp();
        $root = $app->path();
        $paths = [
            'assets'    => "$root/public/theme",
            'uploads'   => "$root/public/uploads",
            'config'    => "$root/config",
            'resources' => "$root/resources"
        ];

        $this->assertSame($app, $app->setPaths($paths));

        $url = $app->path('resources/lang');
        $this->assertEquals((string)$app->path()->append('resources/lang'), "$url");
    }

    public function test_path_builds_paths_from_assigned_paths()
    {
        $app = $this->newApp();
        $root = $app->path();
        $paths = [
            'assets'    => "$root/public/theme",
            'uploads'   => "$root/public/uploads",
            'config'    => "$root/config",
            'resources' => "$root/resources"
        ];

        $this->assertSame($app, $app->setPaths($paths));

        // Test the static access too
        $app->boot();

        $file = 'glyphicons/hello.png';

        foreach ($paths as $key=>$path) {
            $url = $app->path("$key::$file");
            $awaited = (new Url($path))->append($file);
            $this->assertEquals("$awaited", "$url");
            $this->assertEquals("$awaited", (string)Application::to("$key::$file"));
        }

    }

    /**
     * @expectedException \Ems\Contracts\Core\Errors\NotFound
     */
    public function test_path_throws_exception_if_scope_unknown()
    {
        $app = $this->newApp();
        $app->path('bla::home');
    }

    public function test_url_and_setUrl()
    {
        $app = $this->newApp();
        $url = new Url('https://github.com');
        $this->assertSame($app, $app->setUrl($url));
        $this->assertSame($url, $app->url());
    }

    public function test_getConfig_and_setConfig()
    {
        $app = $this->newApp();
        $config = [
            'assets'    => 'public/theme',
            'uploads'   => 'public/uploads',
            'config'    => 'config',
            'resources' => 'resources'
        ];

        $this->assertSame($app, $app->setConfig($config));
        $this->assertEquals($config, $app->getConfig());
    }

    public function test_config()
    {
        $app = $this->newApp();
        $config = [
            'assets'    => 'public/theme',
            'uploads'   => 'public/uploads',
            'config'    => 'config',
            'resources' => 'resources'
        ];

        $this->assertSame($app, $app->setConfig($config));
        $this->assertEquals($config['assets'], $app->config('assets'));
        $this->assertTrue($app->config('throw_exceptions', true));
    }

    public function test_configure()
    {
        $app = $this->newApp();
        $config = [
            'assets'    => 'public/theme',
            'uploads'   => 'public/uploads',
            'config'    => 'config',
            'resources' => 'resources'
        ];

        $this->assertSame($app, $app->setConfig($config));

        $this->assertEquals($config['assets'], $app->config('assets'));
        $this->assertSame($app, $app->configure('assets', 'foo'));
        $this->assertEquals('foo', $app->config('assets'));

        $app->configure($config);
        $app->boot();

        foreach ($config as $key=>$value) {
            $this->assertEquals($config[$key], $app->config($key));
            $this->assertEquals($config[$key], Application::setting($key));
        }

    }

    public function test_get_and_set_environment()
    {
        $app = $this->newApp();
        $this->assertEquals(Application::PRODUCTION, $app->environment());
        $this->assertSame($app, $app->setEnvironment('local'));
        $this->assertEquals('local', $app->environment());
    }

    public function test_get_and_set_environment_by_callable()
    {
        $app = $this->newApp();
        $app->provideEnvironment(function () { return 'testing'; });
        $this->assertEquals('testing', $app->environment());
        $this->assertSame($app, $app->setEnvironment('local'));
        $this->assertEquals('local', $app->environment());
    }

    public function test_methodHooks_returns_something()
    {
        $this->assertContains('boot', $this->newApp()->methodHooks());
    }

    /**
     * @expectedException \Ems\Core\Exceptions\UnsupportedUsageException
     */
    public function test_configure_throws_exception_when_app_was_booted()
    {
        $app = $this->newApp();
        $app->boot();
        $this->assertTrue($app->isBooted());

        $app->configure('assets', 'somewhere-else');

    }

    /**
     * @expectedException \Ems\Core\Exceptions\UnsupportedUsageException
     */
    public function test_setConfig_throws_exception_when_app_was_booted()
    {
        $app = $this->newApp();
        $config = [
            'assets'    => 'public/theme',
            'uploads'   => 'public/uploads',
            'config'    => 'config',
            'resources' => 'resources'
        ];

        $app->boot();

        $app->setConfig($config);

    }

    public function test_current_returns_instance()
    {
        $app = $this->newApp();
        $app->boot();

        $this->assertSame($app, Application::current());
    }

    public function test_container_returns_app()
    {
        $app = $this->newApp();
        $app->boot();

        foreach (['app'] as $key) {
            $this->assertSame($app, Application::container('app'));
        }
    }

    public function test_container_returns_container()
    {
        $app = $this->newApp();
        $app->boot();
        $container = $app->getContainer();

        foreach (['container', 'ioc'] as $key) {
            $this->assertSame($container, Application::container($key));
        }
    }

    public function test_callStatic_resolves_binding()
    {
        $app = $this->newApp();

        $obj = new Application('');
        $app->bind('foo', function () use ($obj) { return $obj; });

        $app->boot();

        $this->assertSame($obj, Application::foo());
    }

    public function test_callStatic_resolves_binding_and_invokes()
    {
        $app = $this->newApp();

        $obj = function ($a, $b) {
            return [$a, $b];
        };

        $app->bind('foo', function () use ($obj) { return $obj; });

        $app->boot();

        $this->assertEquals([1,2], Application::foo(1,2));
    }

    /**
     * @param string $basePath
     * @param IOCContainer|null $container
     *
     * @return Application
     */
    public function newApp($basePath='/srv/www/my-application', IOCContainer $container=null)
    {
        return new Application($basePath, $container);
    }
}
