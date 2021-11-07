<?php
/**
 *  * Created by mtils on 05.11.2021 at 07:49.
 **/

namespace Ems\Contracts\Model\Schema;

/**
 * A simple class to represent a migration (step). Named MigrationStep to not
 * confuse it with "write a migration".
 */
class MigrationStep
{
    /**
     * The relative file path from its root file system.
     *
     * @var string
     */
    public $file = '';

    /**
     * The batch number it did run (if it did)
     *
     * @var int
     */
    public $batch = 0;

    /**
     * Was this migration applied? Should be (bool)$this->batch
     *
     * @var bool
     */
    public $migrated = false;
}