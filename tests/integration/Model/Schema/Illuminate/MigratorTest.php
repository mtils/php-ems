<?php
/**
 *  * Created by mtils on 13.11.2021 at 19:39.
 **/

namespace Model\Schema\Illuminate;

use Ems\Contracts\Core\Filesystem;
use Ems\Contracts\Model\Exceptions\MigratorInstallationException;
use Ems\Contracts\Model\Schema\MigrationRunner;
use Ems\Contracts\Model\Schema\MigrationStep;
use Ems\Contracts\Model\Schema\MigrationStepRepository;
use Ems\Contracts\Model\Schema\Migrator as MigratorContract;
use Ems\Skeleton\Application;
use Ems\Core\LocalFilesystem;
use Ems\Core\Url;
use Ems\IntegrationTest;
use Ems\Model\Schema\Illuminate\IlluminateMigrationStepRepository;
use Ems\Model\Schema\Migrator;
use Ems\Model\Skeleton\MigrationBootstrapper;
use Ems\Testing\LoggingCallable;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Database\Migrations\MigrationRepositoryInterface;
use Mockery\Mock;

use function array_slice;
use function basename;

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
     * @test
     */
    public function it_does_migrate_and_rollback()
    {
        /** @var Filesystem|Mock $fs */
        $fs = $this->mock(Filesystem::class);

        $source = new Url($this->app()->config('migrations')['source']);

        $table = $source->path->first();

        /** @var DatabaseMigrationRepository $laravelRepo */
        $laravelRepo = $this->app()->create(
            DatabaseMigrationRepository::class,
            ['table' => $table]
        );

        /** @var MigrationStepRepository $repository */
        $repository = $this->app()->create(IlluminateMigrationStepRepository::class, [
            'nativeRepository' => $laravelRepo,
            'fs' => $fs
        ]);

        $files = $this->migrationFiles();
        $firstBatchSize = 2;

        $firstBatch = array_slice($files, 0, $firstBatchSize);

        $fs->shouldReceive('files')->andReturn($firstBatch)->once();
        $fs->shouldReceive('basename')->andReturnUsing(function ($path) {
            return basename($path);
        });

        $migrator = $this->make($repository);
        $migrator->install();

        $processed = $migrator->migrate();
        foreach ($processed as $step) {
            $this->assertEquals(1, $step->batch);
        }
        $this->assertCount($firstBatchSize, $processed);

        $this->assertCount($firstBatchSize, $laravelRepo->getConnection()->table($table)->get());

        $fs->shouldReceive('files')->andReturn(array_slice($files, 0, 5))->once();

        $processed = $migrator->migrate();

        $this->assertCount(3, $processed);

        $this->assertCount(5, $laravelRepo->getConnection()->table($table)->get());

        foreach ($processed as $step) {
            $this->assertEquals(2, $step->batch);
        }

        $fs->shouldReceive('files')->andReturn($files);

        $migrations = $migrator->migrations();

        $this->assertCount(count($files), $migrations);

        $migrated = 0;
        $notMigrated = 0;
        foreach ($migrations as $migration) {
            if ($migration->migrated) {
                $migrated++;
                continue;
            }
            $notMigrated++;
        }
        $this->assertEquals(5, $migrated);
        $this->assertEquals(count($files)-5, $notMigrated);

        $lastProcessed = $migrator->rollback();
        $this->assertCount(3, $lastProcessed);

        $this->assertCount($firstBatchSize, $laravelRepo->getConnection()->table($table)->get());

        $processed = $migrator->migrate();

        $this->assertCount(count($files) - $firstBatchSize, $processed);

        $this->assertCount(count($files), $laravelRepo->getConnection()->table($table)->get());

    }

    /**
     * @return Migrator
     */
    protected function make(MigrationStepRepository $repository=null)
    {
        if (!$repository) {
            return $this->app(MigratorContract::class);
        }
        /** @var Migrator $migrator */
        $migrator =  $this->app()->create(Migrator::class, [
            'repository' => $repository
        ]);
        $migrator->setOption(Migrator::PATHS, $this->app()->config('migrations')['paths']);
        return $migrator;
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
            'source'     => 'database://default/migrations',
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