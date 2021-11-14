<?php
/**
 *  * Created by mtils on 14.11.2021 at 09:01.
 **/

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder as Schema;

return new class () {

    public function up(Schema $schema)
    {
        $schema->create('contacts', function(Blueprint $table) {

            $table->increments('id');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('company');
            $table->string('address');
            $table->string('city');
            $table->string('county');
            $table->string('postal');
            $table->string('phone1');
            $table->string('phone2');
            $table->timestamps();

            // We'll need to ensure that MySQL uses the InnoDB engine to
            // support the indexes, other engines aren't affected.
            $table->engine = 'InnoDB';

        });
    }

    public function down(Schema $schema)
    {
        $schema->dropIfExists('contacts');
    }

};