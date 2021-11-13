<?php
/**
 *  * Created by mtils on 11.11.2021 at 22:23.
 **/

namespace Model\Schema\Illuminate;

use Closure;
use Ems\Contracts\Model\Exceptions\MigrationClassNotFoundException;
use Ems\Contracts\Model\Schema\MigrationRunner;
use Ems\Core\Application;
use Ems\IntegrationTest;
use Ems\Model\Eloquent\EmsConnectionFactory;
use Ems\Model\Schema\Illuminate\IlluminateMigrationRunner;
use Ems\Model\Skeleton\MigrationBootstrapper;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Builder;
use Illuminate\Database\Schema\Grammars\Grammar;

class IlluminateMigrationRunnerTest extends IntegrationTest
{
    protected $extraBootstrappers = [MigrationBootstrapper::class];

    /**
     * @test
     */
    public function it_implements_interface()
    {
        $runner = $this->make();
        $this->assertInstanceOf(MigrationRunner::class, $runner);
        $this->assertInstanceOf(IlluminateMigrationRunner::class, $runner);
    }

    /**
     * @test
     */
    public function it_pretends_on_simulate()
    {
        $connections = $this->mock(EmsConnectionFactory::class);
        $connection = $this->mock(Connection::class);
        $runner = $this->make($connections);
        $file = $this->migrationFile('2014_05_26_092001_anonymous_root_class.php');

        $connections->shouldReceive('getDefaultConnection')->andReturn('tests');
        $connections->shouldReceive('connection')->andReturn($connection);
        $connection->shouldReceive('listen');

        $connection->shouldReceive('pretend')->withArgs(function ($runner) {
            return $runner instanceof Closure;
        })->once();

        $runner->upgrade($file, true);
    }

    /**
     * @test
     */
    public function it_runs_within_transaction_if_supported()
    {
        $connections = $this->mock(EmsConnectionFactory::class);
        $connection = $this->mock(Connection::class);
        $grammar = $this->mock(Grammar::class);
        $runner = $this->make($connections);
        $file = $this->migrationFile('2014_05_26_092001_anonymous_root_class.php');

        $connections->shouldReceive('getDefaultConnection')->andReturn('tests');
        $connections->shouldReceive('connection')->andReturn($connection);
        $connection->shouldReceive('listen');
        $connection->shouldReceive('getSchemaGrammar')->andReturn($grammar);
        $grammar->shouldReceive('supportsSchemaTransactions')->andReturn(true);

        $connection->shouldReceive('transaction')->withArgs(function ($runner) {
            return $runner instanceof Closure;
        })->once();

        $runner->upgrade($file, false);
    }

    /**
     * @test
     */
    public function it_runs_without_transaction_if_not_supported()
    {
        $connections = $this->mock(EmsConnectionFactory::class);
        $connection = $this->mock(Connection::class);
        $grammar = $this->mock(Grammar::class);
        $schemaBuilder = $this->mock(Builder::class);

        $runner = $this->make($connections);
        $file = $this->migrationFile('2014_05_26_092001_anonymous_root_class.php');

        $connections->shouldReceive('getDefaultConnection')->andReturn('tests');
        $connections->shouldReceive('connection')->andReturn($connection);
        $connection->shouldReceive('listen');
        $connection->shouldReceive('getSchemaGrammar')->atLeast()->once()->andReturn($grammar);
        $connection->shouldReceive('getSchemaBuilder')->atLeast()->once()->andReturn($schemaBuilder);
        $grammar->shouldReceive('supportsSchemaTransactions')->andReturn(false);
        $schemaBuilder->shouldReceive('create')->once();

        $connection->shouldReceive('transaction')->never();

        $runner->upgrade($file, false);
    }

    /**
     * @test
     */
    public function it_uses_custom_migration_connection()
    {
        $connections = $this->mock(EmsConnectionFactory::class);
        $connection = $this->mock(Connection::class);
        $grammar = $this->mock(Grammar::class);
        $runner = $this->make($connections);
        $file = $this->migrationFile('2015_05_26_092001_anonymous_migration_class.php');

        $connections->shouldReceive('getDefaultConnection')->andReturn('tests');
        $connections->shouldReceive('connection')->with('foo')->andReturn($connection);
        $connection->shouldReceive('listen');
        $connection->shouldReceive('getSchemaGrammar')->andReturn($grammar);
        $grammar->shouldReceive('supportsSchemaTransactions')->andReturn(true);

        $connection->shouldReceive('transaction')->withArgs(function ($runner) {
            return $runner instanceof Closure;
        })->once();

        $runner->upgrade($file, false);
    }

    /**
     * @test
     */
    public function it_supports_standard_classes()
    {
        $connections = $this->mock(EmsConnectionFactory::class);
        $connection = $this->mock(Connection::class);
        $grammar = $this->mock(Grammar::class);
        $runner = $this->make($connections);
        $file = $this->migrationFile('2016_05_26_092001_real_class.php');

        $connections->shouldReceive('getDefaultConnection')->andReturn('tests');
        $connections->shouldReceive('connection')->andReturn($connection);
        $connection->shouldReceive('listen');
        $connection->shouldReceive('getSchemaGrammar')->andReturn($grammar);
        $grammar->shouldReceive('supportsSchemaTransactions')->andReturn(true);

        $connection->shouldReceive('transaction')->withArgs(function ($runner) {
            return $runner instanceof Closure;
        })->once();

        $runner->upgrade($file, false);
    }

    /**
     * @test
     */
    public function it_supports_standard_classes_with_dependencies()
    {
        $connections = $this->mock(EmsConnectionFactory::class);
        $connection = $this->mock(Connection::class);
        $grammar = $this->mock(Grammar::class);
        $runner = $this->make($connections);
        $file = $this->migrationFile('2016_05_26_092001_real_class_with_dependencies.php');

        $connections->shouldReceive('getDefaultConnection')->andReturn('tests');
        $connections->shouldReceive('connection')->andReturn($connection);
        $connection->shouldReceive('listen');
        $connection->shouldReceive('getSchemaGrammar')->andReturn($grammar);
        $grammar->shouldReceive('supportsSchemaTransactions')->andReturn(true);

        $connection->shouldReceive('transaction')->withArgs(function ($runner) {
            return $runner instanceof Closure;
        })->once();

        $runner->downgrade($file, false);
    }

    /**
     * @test
     */
    public function it_throws_exception_if_migration_class_not_found()
    {
        $file = $this->migrationFile('2017_05_26_092001_no_class_in_file.php');
        $this->expectException(MigrationClassNotFoundException::class);
        $this->make()->upgrade($file);
    }

    /**
     * @return IlluminateMigrationRunner
     */
    protected function make(EmsConnectionFactory $connectionFactory=null)
    {

        if (!$connectionFactory) {
            return $this->app(MigrationRunner::class);
        }
        $args = ['connectionFactory' => $connectionFactory];
        /** @var IlluminateMigrationRunner $runner */
        $runner = $this->app()->create(IlluminateMigrationRunner::class, $args);
        $runner->createObjectsBy($this->app());
        return $runner;

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
                $this->dirOfTests('database/schema/migration-tests')
            ]
        ]);

    }

    protected function migrationFile(string $file)
    {
        return $this->app()->config('migrations')['paths'][0] . "/$file";
    }
}