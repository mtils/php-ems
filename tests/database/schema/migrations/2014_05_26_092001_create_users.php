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
        $schema->create('users', function(Blueprint $table) {

            $table->increments('id');
            $table->string('login');
            $table->string('email');
            $table->string('password');
            $table->string('web');
            $table->integer('contact_id');
            $table->integer('parent_id');

            /** @noinspection PhpUndefinedMethodInspection */
            $table->string('locale',12)->default('de_DE');
            $table->timestamps();

            // We'll need to ensure that MySQL uses the InnoDB engine to
            // support the indexes, other engines aren't affected.
            $table->engine = 'InnoDB';
            $table->unique('login');
            $table->unique('email');
            $table->foreign('contact_id')->references('id')->on('contacts');
            $table->foreign('parent_id')->references('id')->on('users');

        });
    }

    public function down(Schema $schema)
    {
        $schema->dropIfExists('users');
    }

};