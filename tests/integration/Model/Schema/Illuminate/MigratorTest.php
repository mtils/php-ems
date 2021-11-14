<?php
/**
 *  * Created by mtils on 13.11.2021 at 19:39.
 **/

namespace Model\Schema\Illuminate;

use Ems\Contracts\Model\Exceptions\MigratorInstallationException;
use Ems\Contracts\Model\Schema\MigrationStep;
use Ems\Contracts\Model\Schema\Migrator as MigratorContract;
use Ems\Core\Application;
use Ems\Core\LocalFilesystem;
use Ems\Core\Url;
use Ems\IntegrationTest;
use Ems\Model\Schema\Migrator;
use Ems\Model\Skeleton\MigrationBootstrapper;
use Ems\Testing\LoggingCallable;
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
    public function it_does_migrate_and_fires_hooks()
    {
        $migrator = $this->make();
        $migrator->install();

        foreach ($migrator->migrations() as $step) {
            $this->assertFalse($step->migrated);
            $this->assertEquals(0, $step->batch);
        }
        $beforeListener = new LoggingCallable();
        $afterListener = new LoggingCallable();
        $migrator->onBefore('upgrade', $beforeListener);
        $migrator->onAfter('upgrade', $afterListener);
        $processed = $migrator->migrate();

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
        $this->assertCount($migrationCount, $beforeListener);
        $this->assertCount($migrationCount, $afterListener);
        $this->assertCount($migrationCount, $processed);

        for($i=0; $i<$migrationCount; $i++) {
            $this->assertInstanceOf(MigrationStep::class, $beforeListener->arg(0, $i));
            $this->assertInstanceOf(MigrationStep::class, $afterListener->arg(0, $i));
        }
    }

    // Test rollback, multiple batches

    /**
     * @test
     */
    public function it_does_simulate_and_fires_queries()
    {

        $migrator = $this->make();
        $migrator->install();

        foreach ($migrator->migrations() as $step) {
            $this->assertFalse($step->migrated);
            $this->assertEquals(0, $step->batch);
        }

        $beforeListener = new LoggingCallable();
        $afterListener = new LoggingCallable();

        $migrator->onBefore('query', $beforeListener);
        $migrator->onAfter('query', $afterListener);

        $migrator->migrate(false, true);
        $migrationCount = 0;
        foreach ($migrator->migrations() as $step) {
            $this->assertFalse($step->migrated);
            $this->assertEquals(0, $step->batch);
            $migrationCount++;
        }

        $this->assertGreaterThan(0, $migrationCount);
        $this->assertCount($migrationCount, $this->migrationFiles());

        /** @var Connection $con */
        $con = $this->app(ConnectionResolverInterface::class)->connection();
        $source = new Url($this->app()->config('migrations')['source']);
        $table = $source->path->first();
        $this->assertTrue($con->getSchemaBuilder()->hasTable($table));
        $this->assertEquals(0, $con->table($table)->count());

        $foundCreate = false;
        for ($i=0; $i<count($beforeListener); $i++) {
            $sql = $beforeListener->arg(0, $i);
            if (strpos(strtolower(trim($sql)), 'create') === 0) {
                $foundCreate = true;
            }
        }
        $this->assertTrue($foundCreate);
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