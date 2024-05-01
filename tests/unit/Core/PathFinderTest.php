<?php

namespace Ems\Core;

use Ems\Contracts\Core\AppPath as AppPathContract;
use OutOfBoundsException;

class PathFinderTest extends \Ems\TestCase
{
    public function test_implements_interface()
    {
        $this->assertInstanceOf(
            'Ems\Contracts\Core\PathFinder',
            $this->newFinder()
        );
    }

    public function test_relative_forwards_to_root_AppPath()
    {
        $appPath = $this->mockAppPath();
        $finder = $this->newFinder($appPath);

        $test = 'foo';
        $result = 'bar';

        $appPath->shouldReceive('relative')
                ->with($test)
                ->once()
                ->andReturn($result);

        $this->assertEquals($result, $finder->relative($test));
    }

    public function test_absolute_forwards_to_root_AppPath()
    {
        $appPath = $this->mockAppPath();
        $finder = $this->newFinder($appPath);

        $test = 'foo';
        $result = 'bar';

        $appPath->shouldReceive('absolute')
                ->with($test)
                ->once()
                ->andReturn($result);

        $this->assertEquals($result, $finder->absolute($test));
    }

    public function test_url_forwards_to_root_AppPath()
    {
        $appPath = $this->mockAppPath();
        $finder = $this->newFinder($appPath);

        $test = 'foo';
        $result = 'bar';

        $appPath->shouldReceive('url')
                ->with($test)
                ->once()
                ->andReturn($result);

        $this->assertEquals($result, $finder->url($test));
    }

    public function test_toString_forwards_to_root_AppPath()
    {
        $appPath = $this->mockAppPath();
        $finder = $this->newFinder($appPath);

        $result = 'bar';

        $appPath->shouldReceive('__toString')
                ->once()
                ->andReturn($result);

        $this->assertEquals($result, "$finder");
    }

    public function test_map_path_and_url_assigns_appPath()
    {
        $finder = $this->newFinder();

        $result = 'bar';

        $appPath = $finder->map('assets', '/srv/www/assets', 'localhost://assets');

        $this->assertInstanceOf(AppPathContract::class, $appPath);
        $this->assertSame($appPath, $finder->to('assets'));
    }

    public function test_throws_OutOfBoundsException_if_scope_was_not_mapped()
    {
        $this->expectException(OutOfBoundsException::class);
        $finder = $this->newFinder();

        $result = 'bar';

        $appPath = $finder->map('assets', '/srv/www/assets', 'localhost://assets');

        $finder->to('assets::foo');
    }

    public function test_scopes_returns_all_mapped_scopes()
    {
        $finder = $this->newFinder();

        $appPath = $finder->map('assets', '/srv/www/assets', 'localhost://assets');

        $this->assertEquals([PathFinder::ROOT, 'assets'], $finder->scopes());
    }

    public function test_namespaced_returns_different_instance()
    {
        $finder = $this->newFinder();

        $namespaced = $finder->namespaced('uploads');

        $this->assertNotSame($finder, $namespaced);
        $this->assertInstanceOf('Ems\Contracts\Core\PathFinder', $namespaced);
    }

    public function test_namespaced_maps_to_namespaced_version()
    {
        $finder = $this->newFinder();

        $namespaced = $finder->namespaced('uploads');

        $appPath = $namespaced->map('js', '/srv/www/js', 'http://localhost/js');

        $this->assertInstanceOf(AppPathContract::class, $appPath);
        $this->assertSame($appPath, $finder->to('uploads::js'));
        $this->assertSame($appPath, $namespaced->to('js'));
    }

    public function test_namespaced_returns_only_scopes_within_namespace()
    {
        $finder = $this->newFinder();

        $namespaced = $finder->namespaced('uploads');

        $finder->map('css', 'a', 'b');
        $namespaced->map('js', '/srv/www/js', 'http://localhost/js');
        $namespaced->map('images', '/srv/www/images', 'http://localhost/images');

        $all = ['app', 'css','uploads::js', 'uploads::images'];
        $filtered = ['uploads::js', 'uploads::images'];

        $this->assertEquals($all, $finder->scopes());
        $this->assertEquals($filtered, $namespaced->scopes());
    }


    public function newFinder($path=null, $url=null)
    {
        if ($path instanceof AppPathContract) {
            $appPath = $path;
        } elseif ($path === null) {
            $appPath = $this->newAppPath('/srv/www/app', 'http://localhost');
        } elseif ($path && $url) {
            $appPath = $this->newAppPath($path, $url);
        }

        $finder = new PathFinder();
        $finder->map(PathFinder::ROOT, $appPath);
        return $finder;
    }

    public function newAppPath($basePath='', $baseUrl='')
    {
        return (new AppPath())->setBasePath($basePath)
                            ->setBaseUrl($baseUrl);
    }

    public function mockAppPath()
    {
        return $this->mock(AppPathContract::class);
    }
}
