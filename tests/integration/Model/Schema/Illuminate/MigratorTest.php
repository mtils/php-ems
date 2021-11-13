<?php
/**
 *  * Created by mtils on 13.11.2021 at 19:39.
 **/

namespace Model\Schema\Illuminate;

use Ems\Contracts\Model\Exceptions\MigratorInstallationException;
use Ems\Contracts\Model\Schema\Migrator as MigratorContract;
use Ems\Core\Application;
use Ems\Core\LocalFilesystem;
use Ems\Core\Url;
use Ems\IntegrationTest;
use Ems\Model\Schema\Migrator;
use Ems\Model\Skeleton\MigrationBootstrapper;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolverInterface;

class MigratorTest extends IntegrationTest
{
    protected $extraBootstrappers = [MigrationBootstrapper::class];

    /**
     * @test
     */
    public function it_implements_interface()
    {
        $this->assertInstanceOf(MigratorContract::class, $this->make());
    }

    /**
     * @test
     */
    public function it_throws_exception_if_repository_not_exists()
    {
        $this->expectException(MigratorInstallationException::class);
        $this->make()->migrations();
    }

    /**
     * @test
     */
    public function it_installs_repository()
    {
        $migrator = $this->make();
        $migrator->install();
        $this->assertCount(count($this->migrationFiles()), $migrator->migrations());
    }

    /**
     * @test
     */
    public function it_does_migrate()
    {
        $migrator = $this->make();
        $migrator->install();

        foreach ($migrator->migrations() as $step) {
            $this->assertFalse($step->migrated);
            $this->assertEquals(0, $step->batch);
        }
        $migrator->migrate();
        $migrationCount = 0;
        foreach ($migrator->migrations() as $step) {
            $this->assertTrue($step->migrated);
            $this->assertEquals(1, $step->batch);
            $migrationCount++;
        }

        $this->assertGreaterThan(0, $migrationCount);
        $this->assertCount($migrationCount, $this->migrationFiles());

        /** @var Connection $con */
        $con = $this->app(ConnectionResolverInterface::class)->connection();
        $source = new Url($this->app()->config('migrations')['source']);
        $table = $source->path->first();
        $this->assertTrue($con->getSchemaBuilder()->hasTable($table));
        $this->assertEquals($migrationCount, $con->table($table)->count());

    }

    /**
     * @return Migrator
     */
    protected function make()
    {
        return $this->app(MigratorContract::class);
    }

    protected function configureApplication(Application $app)
    {
        parent::configureApplication($app);

        $app->configure('database', [
            'connection' => 'tests',
            'connections' => [
                'tests' => [
                    'driver'    => 'sqlite',
                    'database'  => ':memory:'
                ]
            ]
        ]);
        $app->configure('migrations', [
            'repository' => 'illuminate',
            'runner'     => 'illuminate',
            'source'     => 'database://tests/migrations',
            'paths'      => [
                $this->dirOfTests('database/schema/migrations')
            ]
        ]);

    }

    /**
     * @return string[]
     */
    protected function migrationFiles() : array
    {
        $fs = new LocalFilesystem();
        $files = [];
        foreach ($this->app()->config('migrations')['paths'] as $path) {
            foreach ($fs->files($path, '*_*', 'php') as $file) {
                $files[$fs->basename($file)] = $file;
            }
        }
        return $files;
    }
}