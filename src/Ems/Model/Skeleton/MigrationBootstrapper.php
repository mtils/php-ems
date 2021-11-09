<?php
/**
 *  * Created by mtils on 07.11.2021 at 20:24.
 **/

namespace Ems\Model\Skeleton;

use Ems\Contracts\Core\Configurable;
use Ems\Contracts\Core\ConnectionPool;
use Ems\Contracts\Model\Schema\MigrationRunner;
use Ems\Contracts\Model\Schema\MigrationStepRepository;
use Ems\Core\Application;
use Ems\Core\Exceptions\NotImplementedException;
use Ems\Core\Skeleton\Bootstrapper;
use Ems\Core\Url;
use Ems\Model\Eloquent\EmsConnectionFactory;
use Ems\Model\Schema\Illuminate\IlluminateMigrationRunner;
use Ems\Model\Schema\Illuminate\IlluminateMigrationStepRepository;
use Ems\Model\Schema\Migrator;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Database\Migrations\MigrationRepositoryInterface;
use Ems\Contracts\Model\Schema\Migrator as MigratorContract;

use function in_array;
use function var_dump;

class MigrationBootstrapper extends Bootstrapper
{
    protected $defaultConfig = [
        'repository'    => 'illuminate',
        'runner'        => 'illuminate',
        'source'        => 'database://default/migrations',
        'paths'         => ['resources/database/migrations']
    ];

    public function bind()
    {
        parent::bind();

        if (!$this->app->has(ConnectionResolverInterface::class)) {
            $this->app->share(ConnectionResolverInterface::class, function () {
                /** @var EmsConnectionFactory $factory */
                $factory = $this->app->create(EmsConnectionFactory::class, [
                    'connectionPool' => $this->app->get(ConnectionPool::class)
                ]);
                /** @var Application $app */
                $app = $this->app->get(Application::class);
                if (!$dbConfig = $app->config('database')) {
                    return $factory;
                }
                if (isset($dbConfig['connection']) && $dbConfig['connection']) {
                    $factory->setDefaultConnection($dbConfig['connection']);
                }
                return $factory;
            });
        }

        $this->app->share(MigrationStepRepository::class, function () {
            return $this->makeRepository($this->getConfig());
        });

        $this->app->bind(MigrationRunner::class, function () {
            return $this->makeRunner($this->getConfig());
        });

        $this->app->share(MigratorContract::class, function () {
            $migrator = $this->app->create(Migrator::class);
            if ($migrator instanceof Configurable) {
                $this->configure($migrator, $this->getConfig());
            }
            return $migrator;
        });
    }

    protected function configure(Configurable $handler, array $config)
    {
        $options = $handler->supportedOptions();
        $source = new Url($config['source']);

        if (in_array(MigratorContract::PATHS, $options)) {
            $handler->setOption(MigratorContract::PATHS, $config['paths']);
        }
        if (in_array(MigratorContract::REPOSITORY_URL, $options)) {
            $handler->setOption(MigratorContract::REPOSITORY_URL, $source);
        }
    }

    protected function makeRunner(array $config) : MigrationRunner
    {
        $backend = $config['runner'];

        if ($backend != 'illuminate') {
            throw new NotImplementedException("Unknown runner backend '$backend'");
        }

        /** @var IlluminateMigrationRunner $runner */
        $runner = $this->app->create(IlluminateMigrationRunner::class);
        if ($runner instanceof Configurable) {
            $this->configure($runner, $config);
        }

        return $runner;
    }

    protected function makeRepository(array $config) : MigrationStepRepository
    {
        $backend = $config['repository'];
        $source = new Url($config['source']);

        if ($backend != 'illuminate') {
            throw new NotImplementedException("Unknown repository backend '$backend'");
        }

        $this->app->bind(MigrationRepositoryInterface::class, function () use ($source) {
            return $this->makeLaravelRepository($source);
        });

        /** @var IlluminateMigrationStepRepository $repository */
        $repository = $this->app->create(IlluminateMigrationStepRepository::class);

        if ($repository instanceof Configurable) {
            $this->configure($repository, $config);
        }

        return $repository;

    }

    protected function makeLaravelRepository(Url $source) : MigrationRepositoryInterface
    {
        if ($source->scheme != 'database') {
            throw new NotImplementedException("The illuminate repository driver only supports a database.");
        }
        /** @var DatabaseMigrationRepository $migrationRepo */
        $migrationRepo = $this->app->create(DatabaseMigrationRepository::class, [
            'table' => $source->path->first()
        ]);
        $migrationRepo->setSource($source->host);

        return $migrationRepo;

    }

    protected function getConfig($key=null)
    {
        /** @var Application $app */
        $app = $this->app->get(Application::class);
        $config = $app->config('migrations', $this->defaultConfig);
        return $key ? $config[$key] : $config;
    }
}