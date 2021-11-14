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
        $schema->create('project_file', function(Blueprint $table) {

            $table->integer('project_id');
            $table->integer('file_id');
            $table->timestamps();

            // We'll need to ensure that MySQL uses the InnoDB engine to
            // support the indexes, other engines aren't affected.
            $table->engine = 'InnoDB';
            $table->unique(['file_id', 'project_id']);
            $table->foreign('file_id')->references('id')->on('files');
            $table->foreign('project_id')->references('id')->on('projects');

        });
    }

    public function down(Schema $schema)
    {
        $schema->dropIfExists('project_file');
    }

};