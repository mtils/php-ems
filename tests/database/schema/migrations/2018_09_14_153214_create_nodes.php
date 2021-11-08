<?php
/**
 *  * Created by mtils on 06.11.2021 at 07:05.
 **/

use Ems\Model\Schema\Illuminate\IlluminateMigration as Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder as Schema;

return new class() extends Migration
{
    public function up(Schema $schema)
    {
        $schema->create('nodes', function(Blueprint $table) {

            $table->increments('id');

            /** @noinspection PhpUndefinedMethodInspection */
            $table->string('name')->nullable();
            /** @noinspection PhpUndefinedMethodInspection */
            $table->string('title')->nullable();
            /** @noinspection PhpUndefinedMethodInspection */
            $table->string('path')->nullable();
            /** @noinspection PhpUndefinedMethodInspection */
            $table->string('parent_id')->nullable();

            // We'll need to ensure that MySQL uses the InnoDB engine to
            // support the indexes, other engines aren't affected.
            $table->engine = 'InnoDB';

        });
    }

    public function down(Schema $schema)
    {
        $schema->dropIfExists('nodes');
    }

};