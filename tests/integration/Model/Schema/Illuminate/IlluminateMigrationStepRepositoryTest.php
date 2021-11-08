<?php
/**
 *  * Created by mtils on 08.11.2021 at 20:56.
 **/

namespace integration\Model\Schema\Illuminate;

use Ems\Contracts\Model\Schema\MigrationStepRepository;
use Ems\Core\Application;
use Ems\IntegrationTest;
use Ems\Model\Schema\Illuminate\IlluminateMigrationStepRepository;
use Ems\Model\Skeleton\MigrationBootstrapper;

class IlluminateMigrationStepRepositoryTest extends IntegrationTest
{
    protected $extraBootstrappers = [MigrationBootstrapper::class];

    /**
     * @test
     */
    public function it_implements_interface()
    {
        $repo = $this->make();
        $this->assertInstanceOf(MigrationStepRepository::class, $repo);
        $this->assertInstanceOf(IlluminateMigrationStepRepository::class, $repo);
    }

    /**
     * @test
     */
    public function it_throws_Exception_if_repository_was_not_installed()
    {
        $repo = $this->make();
        $steps = $repo->all();
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
            'source'     => 'database://default/migrations',
            'paths'      => [
                $this->dirOfTests('database/schema/migrations')
            ]
        ]);
    }

}