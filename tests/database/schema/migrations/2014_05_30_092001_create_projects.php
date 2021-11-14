<?php
/**
 *  * Created by mtils on 06.11.2021 at 06:58.
 **/

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder as Schema;

return new class()
{
    public function up(Schema $schema)
    {
        $schema->create('projects', function(Blueprint $table) {

            $table->increments('id');
            $table->string('name');
            $table->integer('type_id');
            $table->integer('owner_id');
            $table->timestamps();

            // We'll need to ensure that MySQL uses the InnoDB engine to
            // support the indexes, other engines aren't affected.
            $table->engine = 'InnoDB';
            $table->foreign('type_id')->references('id')->on('project_types');
            $table->foreign('owner_id')->references('id')->on('users');

        });
    }

    public function down(Schema $schema)
    {
        $schema->dropIfExists('projects');
    }

};