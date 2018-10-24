<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNodesTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('nodes', function(Blueprint $table) {

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

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('nodes');
    }

}

