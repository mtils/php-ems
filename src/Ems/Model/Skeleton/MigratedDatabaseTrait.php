<?php
/**
 *  * Created by mtils on 21.11.2021 at 12:09.
 **/

namespace Ems\Model\Skeleton;

use Ems\Contracts\Model\Schema\Migrator;
use Ems\Core\Application;

use function get_class;
use function property_exists;

trait MigratedDatabaseTrait
{
    /**
     * @var array
     */
    protected static $completedMigrations = [];

    protected function afterBootMigrationsForClass(Application $app)
    {
        $class = get_class($this);
        if (isset(static::$completedMigrations[$class]) && static::$completedMigrations[$class]) {
            return;
        }

        static::runMigrations($app);
        if (!static::migratePerTestMethod()) {
            static::$completedMigrations[$class] = true;
        }

    }

    /**
     * Overwrite this method to migrate before every test.
     *
     * @return bool
     */
    protected static function migratePerTestMethod() : bool
    {
        return false;
    }

    protected static function runMigrations(Application $app)
    {
        /** @var Migrator $migrator */
        $migrator = $app(Migrator::class);
        $migrator->install();
        $migrator->migrate();
    }

    protected function connection()
    {
        $class = get_class($this);
        if (property_exists($class, 'con')) {
            return $class::$con;
        }

        if (property_exists($this, 'connection')) {
            return $this->connection;
        }
    }
}