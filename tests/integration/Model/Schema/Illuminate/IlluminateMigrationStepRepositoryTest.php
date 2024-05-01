<?php
/**
 *  * Created by mtils on 08.11.2021 at 20:56.
 **/

namespace Model\Schema\Illuminate;

use Ems\Contracts\Model\Exceptions\MigratorInstallationException;
use Ems\Contracts\Model\Schema\MigrationStep;
use Ems\Contracts\Model\Schema\MigrationStepRepository;
use Ems\Skeleton\Application;
use Ems\Core\LocalFilesystem;
use Ems\IntegrationTest;
use Ems\Model\Schema\Illuminate\IlluminateMigrationStepRepository;
use Ems\Model\Skeleton\MigrationBootstrapper;

use PHPUnit\Framework\Attributes\Test;

use function basename;

class IlluminateMigrationStepRepositoryTest extends IntegrationTest
{
    protected $extraBootstrappers = [MigrationBootstrapper::class];

    #[Test] public function it_implements_interface()
    {
        $repo = $this->make();
        $this->assertInstanceOf(MigrationStepRepository::class, $repo);
        $this->assertInstanceOf(IlluminateMigrationStepRepository::class, $repo);
    }

    #[Test] public function it_throws_Exception_if_repository_was_not_installed()
    {
        $repo = $this->make();
        $this->expectException(MigratorInstallationException::class);
        $repo->all();
    }

    #[Test] public function all_returns_not_migrated_steps_after_installing()
    {
        $repo = $this->make();
        $repo->install();
        $files = $this->migrationFiles();
        $steps = $repo->all();
        $this->assertCount(count($files), $steps);
        foreach ($steps as $step) {
            $baseFile = basename($step->file);
            $this->assertInstanceOf(MigrationStep::class, $step);
            $this->assertEquals(0, $step->batch);
            $this->assertFalse($step->migrated);
            $this->assertTrue(isset($files[$baseFile]));
        }
    }

    #[Test] public function save_saves_migration()
    {
        $repo = $this->make();
        $repo->install();
        $files = $this->migrationFiles();
        $steps = $repo->all();
        $this->assertCount(count($files), $steps);

        $steps[0]->migrated = true;
        $steps[0]->batch = 1;
        $repo->save($steps[0]);

        $steps = $repo->all();
        $this->assertEquals(1, $steps[0]->batch);
        $this->assertTrue($steps[0]->migrated);
        $this->assertEquals(0, $steps[1]->batch);
        $this->assertFalse($steps[1]->migrated);

        $steps[1]->migrated = true;
        $steps[1]->batch = 1;
        $repo->save($steps[1]);

        $steps = $repo->all();
        $this->assertEquals(1, $steps[0]->batch);
        $this->assertTrue($steps[0]->migrated);
        $this->assertEquals(1, $steps[1]->batch);
        $this->assertTrue($steps[1]->migrated);
    }
    /**
     * @return IlluminateMigrationStepRepository
     */
    protected function make()
    {
        return $this->app(MigrationStepRepository::class);
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