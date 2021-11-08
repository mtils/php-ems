<?php
/**
 *  * Created by mtils on 07.11.2021 at 12:07.
 **/

namespace Ems\Contracts\Model\Schema;

interface MigrationStepRepository
{

    /**
     * Get all migrations ordered by sequence asc
     *
     * @return MigrationStep[]
     */
    public function all() : array;

    /**
     * Save the state of $step.
     *
     * @param MigrationStep $step
     *
     * @return bool
     */
    public function save(MigrationStep $step) : bool;

    /**
     * Install the migration repository.
     */
    public function install() : void;
}