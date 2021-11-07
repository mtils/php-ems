<?php
/**
 *  * Created by mtils on 05.11.2021 at 07:11.
 **/

namespace Ems\Contracts\Model\Schema;

/**
 * The migrator runs and rolls migrations back. This is heavily inspired by
 * laravel migrations.
 */
interface Migrator
{
    /**
     * A constant to represent paths, use it for configuration.
     */
    const PATHS = 'paths';

    /**
     * A constant to represent the url where its repository lives. Could be a
     * database url including a table or any other url.
     */
    const REPOSITORY_URL = 'repository_url';

    /**
     * Migrate all pending migrations that are in configured paths.
     *
     * @param bool $onePerBatch Make every migration a batch so that a rollback will be per migration
     * @param bool $simulate Just do as if you would migrate but do not write to the database
     *
     * @return MigrationStep[] All performed migrations, also in simulation mode
     */
    public function migrate(bool $onePerBatch=false, bool $simulate=false) : array;

    /**
     * Rollback the last batch of migrations.
     *
     * @param int $count        Limit the number of rolled back migrations
     * @param bool $simulate    Just do as if you would roll back but do not write to the database
     *
     * @return MigrationStep[] All rolled back migrations, also in simulation mode
     */
    public function rollback(int $count=0, bool $simulate=false): array;

    /**
     * Return all known migration objects
     *
     * @return MigrationStep[]
     */
    public function migrations(): array;
}