<?php

namespace Ems\Testing\Eloquent;

use Ems\Core\LocalFilesystem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Container\Container;

trait MigratedDatabase
{
    use InMemoryConnection;

    /**
     * @var DatabaseMigrationRepository
     **/
    protected static $_migrationRepository;

    /**
     * @var Migrator
     **/
    protected static $_migrator;

    /**
     * {@inheritdoc}
     **/
    protected function refreshConnection()
    {
        $this->createAndInjectConnection();
        $this->migrateDatabase();
    }

    /**
     * Migrates the database either with a app environment
     * or an artificial test environment (for packages like ems)
     **/
    protected function migrateDatabase()
    {
        if ($this->hasRunningApp()) {
            return $this->migrateAppDatabase();
        }
        return $this->migrateAppLessDatabase();
    }

    /**
     * Migrates the database inside a standard laravel app
     **/
    protected function migrateAppDatabase()
    {
        $repo = $this->app->make('migration.repository');
        $repo->setSource($this->connectionName());
        $repo->createRepository();
        $this->app->make('migrator')->run($this->migrationsPath());
    }

    /**
     * Migrates the database without a laravel app
     **/
    protected function migrateAppLessDatabase()
    {
        $this->migrationRepository()->createRepository();
        $this->fakeSchemaFacade();
        $this->migrator()->run($this->migrationsPath());
    }

    /**
     * @return DatabaseMigrationRepository
     **/
    protected function migrationRepository()
    {

        if (!static::$_migrationRepository) {

            $connectionResolver = Model::getConnectionResolver();

            $migrationRepo = new DatabaseMigrationRepository(
                $connectionResolver,
                'migrations'
            );

            $migrationRepo->setSource('tests');

            static::$_migrationRepository = $migrationRepo;
        }

        return static::$_migrationRepository;
    }

    /**
     * @return Migrator
     **/
    public function migrator()
    {

        if (!static::$_migrator) {

            $connectionResolver = Model::getConnectionResolver();
            $repository = $this->migrationRepository();
            $files = new Filesystem;

            static::$_migrator = new Migrator($repository, $connectionResolver, $files);

        }

        return static::$_migrator;

    }

    /**
     * Return the path where your migrations live. Overwrite this method
     * to adapt it to your environment. This method is only called in
     * non-app environments.
     *
     * @return string
     **/
    protected function migrationsPath()
    {
        return $this->packageMigrationPath();
    }

    /**
     * Return the (ems internal) package migration path
     *
     * @return string
     **/
    protected function packageMigrationPath()
    {
         return realpath(__DIR__ . '/../../../../tests/database/migrations');
    }


    /**
     * In many migrations the facades are used, so a minumum has to be
     * created to run all migrations
     *
     * @return void
     **/
    protected function fakeSchemaFacade()
    {
        $app = new Container();
        $app->instance('db', Model::getConnectionResolver());

        $app->bind('db.schema', function () use ($app) {
            return $app->make('db')->connection()->getSchemaBuilder();
        });


        if (!class_exists('Schema')) {
            class_alias('Illuminate\Support\Facades\Schema', 'Schema');
        }
        if (!class_exists('DB')) {
            class_alias('Illuminate\Support\Facades\DB', 'DB');
        }


        Schema::setFacadeApplication($app);

        DB::setFacadeApplication($app);

    }

}
