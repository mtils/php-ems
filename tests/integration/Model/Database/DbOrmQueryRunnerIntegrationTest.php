<?php
/**
 *  * Created by mtils on 01.06.20 at 09:27.
 **/

namespace integration\Model\Database;


use Ems\Contracts\Model\OrmQueryRunner;
use Ems\Model\OrmQuery;
use Ems\OrmIntegrationTest;
use Models\User;

class DbOrmQueryRunnerIntegrationTest extends OrmIntegrationTest
{
    /**
     * @test
     */
    public function it_implements_interface()
    {
        $this->assertInstanceOf(OrmQueryRunner::class, $this->queryRunner());
    }

    /**
     * @test
     */
    public function select_one_user()
    {
        $query = (new OrmQuery(User::class))->where('id', 1);
        $this->queryRunner()->retrieve(static::$con, $query);
    }
}