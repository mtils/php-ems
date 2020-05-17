<?php
/**
 *  * Created by mtils on 19.04.20 at 10:20.
 **/

namespace integration\Model\Database;


use DateTime;
use Ems\Contracts\Model\Database\Query as QueryContract;
use Ems\Contracts\Model\OrmQuery;
use Ems\Contracts\Model\SchemaInspector;
use Ems\Core\LocalFilesystem;
use Ems\DatabaseIntegrationTest;

use Ems\Model\Database\OrmQueryBuilder;
use Ems\Model\Database\Query;
use Ems\Model\Database\SQL;
use Ems\Model\MapSchemaInspector;
use Ems\Model\StaticClassMap;
use Models\Contact;
use Models\Ems\ContactMap;
use Models\Ems\UserMap;
use Models\User;

use function assertInstanceOf;
use function basename;
use function class_exists;
use function class_implements;
use function get_class;

class OrmQueryBuilderIntegrationTest extends DatabaseIntegrationTest
{

    /**
     * @var string[]
     */
    protected static $mapClasses = [];

    /**
     * @test
     */
    public function it_instantiates()
    {
        $this->assertInstanceOf(OrmQueryBuilder::class, $this->make());
    }

    /**
     * @test
     */
    public function select_from_contact()
    {
        $query = new OrmQuery(Contact::class);
        $dbQuery = static::$con->query();
        $inspector = $this->newInspector();

        /** @var Query $dbQuery */
        $dbQuery = $this->make($inspector)->toSelect($query, $dbQuery);

        $this->assertInstanceOf(Query::class, $dbQuery);
        $this->assertCount(count($inspector->getKeys(Contact::class)), $dbQuery->columns);
        $this->assertEquals($inspector->getStorageName(Contact::class), $dbQuery->table);
        $this->assertCount(0, $dbQuery->conditions);
        $this->assertCount(0, $dbQuery->joins);
    }

    /**
     * @test
     */
    public function select_from_contact_where()
    {
        $query = new OrmQuery(Contact::class);
        $dbQuery = static::$con->query();
        $inspector = $this->newInspector();

        $query->where(ContactMap::CITY, 'like', 'C%')
              ->where(ContactMap::ID, '>', 200);

        /** @var Query $dbQuery */
        $dbQuery = $this->make($inspector)->toSelect($query, $dbQuery);
        $this->assertInstanceOf(Query::class, $dbQuery);
        $this->assertCount(count($inspector->getKeys(Contact::class)), $dbQuery->columns);
        $this->assertEquals($inspector->getStorageName(Contact::class), $dbQuery->table);

        $this->assertCount(2, $dbQuery->conditions);
        $this->assertCount(0, $dbQuery->joins);

    }

    /**
     * @test
     */
    public function select_from_user_where_related()
    {
        $query = new OrmQuery(User::class);
        $dbQuery = static::$con->query();
        $inspector = $this->newInspector();

        $query->where('contact.'.ContactMap::CITY, 'like', 'C%')
            ->where(UserMap::EMAIL, 'LIKE', '%@outlook.com')
            ->where(UserMap::CREATED_AT, '>', new DateTime('2020-04-15 00:00:00'));

        /** @var Query $dbQuery */
        $dbQuery = $this->make($inspector)->toSelect($query, $dbQuery);
        echo "\n$dbQuery";
        $this->assertInstanceOf(Query::class, $dbQuery);
        $this->assertCount(count($inspector->getKeys(Contact::class)), $dbQuery->columns);
        $this->assertEquals($inspector->getStorageName(User::class), $dbQuery->table);

        $this->assertCount(3, $dbQuery->conditions);
        $this->assertCount(1, $dbQuery->joins);


    }

    protected function make(SchemaInspector $inspector=null)
    {
        return new OrmQueryBuilder($inspector ?: $this->newInspector());
    }

    protected function newInspector($configure=true)
    {
        $inspector = new MapSchemaInspector();
        if($configure) {
            $this->configureInspector($inspector);
        }
        return $inspector;
    }

    protected function configureInspector(MapSchemaInspector $inspector)
    {
        foreach (static::$mapClasses as $class) {
            /** @var StaticClassMap $map */
            $map = new $class;
            $inspector->map($map->getOrmClass(), $map);
        }
    }

    /**
     * @beforeClass
     * @noinspection PhpIncludeInspection
     */
    public static function loadOrm()
    {
        if(class_exists(User::class)) {
            return;
        }

        $fs = new LocalFilesystem();

        $ormDir = static::dirOfTests('database/orm');
        $mapDir = "$ormDir/map";

        foreach($fs->files($ormDir) as $file) {
            include_once($file);
        }

        foreach($fs->files($mapDir) as $file) {
            $class = "Models\\Ems\\" . $fs->name($file);
            static::$mapClasses[] = $class;
            include_once($file);
        }
    }

}