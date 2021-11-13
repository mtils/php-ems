<?php
/**
 *  * Created by mtils on 06.11.2021 at 06:58.
 **/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder as Schema;

return new class () extends Migration
{
    public function up(Schema $schema)
    {
        $schema->create('users', function(Blueprint $table) {});
    }

    public function down(Schema $schema)
    {
        $schema->dropIfExists('users');
    }

    public function getConnection()
    {
        return 'foo';
    }

};