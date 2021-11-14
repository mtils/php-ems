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
        $schema->create('tokens', function(Blueprint $table) {

            $table->increments('id');
            $table->integer('user_id');
            $table->tinyInteger('token_type');
            $table->text('token');
            $table->dateTime('expires_at');
            $table->timestamps();

            // We'll need to ensure that MySQL uses the InnoDB engine to
            // support the indexes, other engines aren't affected.
            $table->engine = 'InnoDB';
            $table->foreign('user_id')->references('id')->on('users');

        });
    }

    public function down(Schema $schema)
    {
        $schema->dropIfExists('tokens');
    }

};