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
        $schema->create('user_group', function(Blueprint $table) {

            $table->integer('user_id');
            $table->integer('group_id');

            // We'll need to ensure that MySQL uses the InnoDB engine to
            // support the indexes, other engines aren't affected.
            $table->engine = 'InnoDB';
            $table->unique(['user_id', 'group_id']);

            $table->foreign('group_id')->references('id')->on('groups');
            $table->foreign('user_id')->references('id')->on('users');

        });
    }

    public function down(Schema $schema)
    {
        $schema->dropIfExists('user_group');
    }

};