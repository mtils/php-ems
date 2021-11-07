<?php
/**
 *  * Created by mtils on 06.11.2021 at 06:46.
 **/

namespace Ems\Model\Schema\Illuminate;

use Illuminate\Database\Migrations\Migration as BaseMigration;
use Illuminate\Database\Schema\Builder as Schema;

abstract class IlluminateMigration extends BaseMigration
{
    /**
     * Run this migration.
     *
     * @param Schema $schema
     */
    abstract public function up(Schema $schema);

    /**
     * Rollback this migration.
     *
     * @param Schema $schema
     */
    abstract public function down(Schema $schema);
}