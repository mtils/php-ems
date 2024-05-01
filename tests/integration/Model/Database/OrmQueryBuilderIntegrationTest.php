<?php
/**
 *  * Created by mtils on 19.04.20 at 10:20.
 **/

namespace integration\Model\Database;


use DateTime;
use Ems\Contracts\Model\Database\Query as QueryContract;
use Ems\Contracts\Model\OrmQuery;
use Ems\IntegrationTest;
use Ems\Model\Database\OrmQueryBuilder;
use Ems\Model\Database\Query;
use Ems\TestOrm;
use Models\Contact;
use Models\Ems\ContactMap;
use Models\Ems\ProjectTypeMap;
use Models\Ems\UserMap;
use Models\File;
use Models\Project;
use Models\ProjectType;
use Models\Token;
use Models\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * Class OrmQueryBuilderIntegrationTest
 *
 * TODO
 *
 * X join n:1
 * X join 1:n
 * X join m:n
 * X join m:n -> n:1
 * X join self (parent, parent.parent)
 *
 * - eager n:1
 * - eager 1:n
 * - eager m:n
 * - eager n:1 1:n
 * - eager n:1 m:n
 *
 * @package integration\Model\Database
 */
class OrmQueryBuilderIntegrationTest extends IntegrationTest
{
    use TestOrm;

    #[Test] public function it_instantiates()
    {
        $this->assertInstanceOf(OrmQueryBuilder::class, $this->queryBuilder());
    }

    #[Test] public function select_from_contact()
    {
        $query = new OrmQuery(Contact::class);
        $inspector = $this->newInspector();

        $dbQuery = $this->queryBuilder($inspector)->toSelect($query);

        $this->assertInstanceOf(QueryContract::class, $dbQuery);
        $this->assertCount(count($inspector->getKeys(Contact::class)), $dbQuery->columns);
        $this->assertEquals($inspector->getStorageName(Contact::class), $dbQuery->table);
        $this->assertCount(0, $dbQuery->conditions);
        $this->assertCount(0, $dbQuery->joins);
    }

    #[Test] public function select_from_contact_where()
    {
        $query = new OrmQuery(Contact::class);
        $inspector = $this->newInspector();

        $query->where(ContactMap::CITY, 'like', 'C%')
              ->where(ContactMap::ID, '>', 200);

        $dbQuery = $this->queryBuilder($inspector)->toSelect($query);
        $this->assertInstanceOf(QueryContract::class, $dbQuery);
        $this->assertCount(count($inspector->getKeys(Contact::class)), $dbQuery->columns);
        $this->assertEquals($inspector->getStorageName(Contact::class), $dbQuery->table);

        $this->assertCount(2, $dbQuery->conditions);
        $this->assertCount(0, $dbQuery->joins);

    }

    #[Test] public function select_from_user_where_related()
    {
        $query = new OrmQuery(User::class);
        $inspector = $this->newInspector();

        $query->where('contact.'.ContactMap::CITY, 'like', 'C%')
            ->where(UserMap::EMAIL, 'LIKE', '%@outlook.com')
            ->where(UserMap::CREATED_AT, '>', new DateTime('2020-04-15 00:00:00'));

        $dbQuery = $this->queryBuilder($inspector)->toSelect($query);

        $this->assertInstanceOf(QueryContract::class, $dbQuery);
        $this->assertCount(count($inspector->getKeys(User::class)), $dbQuery->columns);
        $this->assertEquals($inspector->getStorageName(User::class), $dbQuery->table);

        $this->assertCount(3, $dbQuery->conditions);
        $this->assertCount(1, $dbQuery->joins);

    }

    #[Test] public function select_from_user_where_multiple_related()
    {
        $query = new OrmQuery(User::class);
        $inspector = $this->newInspector();

        $query->where('projects.type.name', 'like', 'C%')
            ->where(UserMap::EMAIL, 'LIKE', '%@outlook.com')
            ->where(UserMap::CREATED_AT, '>', new DateTime('2020-04-15 00:00:00'));

        /** @var Query $dbQuery */
        $dbQuery = $this->queryBuilder($inspector)->toSelect($query);

        $this->assertInstanceOf(QueryContract::class, $dbQuery);
        $this->assertCount(count($inspector->getKeys(User::class)), $dbQuery->columns);
        $this->assertEquals($inspector->getStorageName(User::class), $dbQuery->table);

        $this->assertCount(3, $dbQuery->conditions);
        $this->assertCount(2, $dbQuery->joins);
        $this->assertTrue($dbQuery->distinct);

    }

    #[Test] public function select_from_projects_where_many_to_many()
    {
        $query = new OrmQuery(Project::class);

        $inspector = $this->newInspector();

        $query->where('files.name', 'like', 'C%')
            ->where('type.name', 'Real Estate');

        $dbQuery = $this->queryBuilder($inspector)->toSelect($query);

        $this->assertInstanceOf(QueryContract::class, $dbQuery);
        $this->assertCount(count($inspector->getKeys(Project::class)), $dbQuery->columns);
        $this->assertEquals($inspector->getStorageName(Project::class), $dbQuery->table);

        $this->assertCount(2, $dbQuery->conditions);
        $this->assertCount(3, $dbQuery->joins);
        $this->assertTrue($dbQuery->distinct);

    }

    #[Test] public function select_from_projects_where_many_to_level2()
    {
        $query = new OrmQuery(User::class);
        $inspector = $this->newInspector();

        $query->where('projects.files.name', 'like', 'C%')
            ->where('contact.first_name', 'Michael');

        /** @var Query $dbQuery */
        $dbQuery = $this->queryBuilder($inspector)->toSelect($query);

        $this->assertInstanceOf(QueryContract::class, $dbQuery);
        $this->assertCount(count($inspector->getKeys(User::class)), $dbQuery->columns);
        $this->assertEquals($inspector->getStorageName(User::class), $dbQuery->table);

        $this->assertCount(2, $dbQuery->conditions);
        $this->assertCount(4, $dbQuery->joins);
        $this->assertTrue($dbQuery->distinct);

    }

    #[Test] public function select_from_user_where_parent()
    {
        $query = new OrmQuery(User::class);
        $inspector = $this->newInspector();

        $query->where('parent.'.UserMap::WEB, 'like', 'https/%')
            ->where(UserMap::EMAIL, 'LIKE', '%@outlook.com')
            ->where(UserMap::CREATED_AT, '>', new DateTime('2020-04-15 00:00:00'));

        /** @var Query $dbQuery */
        $dbQuery = $this->queryBuilder($inspector)->toSelect($query);

        $this->assertInstanceOf(QueryContract::class, $dbQuery);
        $this->assertCount(count($inspector->getKeys(User::class)), $dbQuery->columns);
        $this->assertEquals($inspector->getStorageName(User::class), $dbQuery->table);

        $this->assertCount(3, $dbQuery->conditions);
        $this->assertCount(1, $dbQuery->joins);

    }

    #[Test] public function select_from_user_where_parent_parent()
    {
        $query = new OrmQuery(User::class);
        $inspector = $this->newInspector();

        $query->where('parent.parent.'.UserMap::WEB, 'like', 'https/%')
            ->where(UserMap::EMAIL, 'LIKE', '%@outlook.com')
            ->where(UserMap::CREATED_AT, '>', new DateTime('2020-04-15 00:00:00'));

        $dbQuery = $this->queryBuilder($inspector)->toSelect($query);

        $this->assertInstanceOf(QueryContract::class, $dbQuery);
        $this->assertCount(count($inspector->getKeys(User::class)), $dbQuery->columns);
        $this->assertEquals($inspector->getStorageName(User::class), $dbQuery->table);

        $this->assertCount(3, $dbQuery->conditions);
        $this->assertCount(2, $dbQuery->joins);

    }

    #[Test] public function select_from_user_with_contact()
    {
        $query = (new OrmQuery(User::class))->with('contact');

        $inspector = $this->newInspector();

        $query->where(UserMap::EMAIL, 'LIKE', '%@outlook.com')
            ->where(UserMap::CREATED_AT, '>', new DateTime('2020-04-15 00:00:00'));

        $dbQuery = $this->queryBuilder($inspector)->toSelect($query);

        $this->assertInstanceOf(QueryContract::class, $dbQuery);
        $this->assertEquals($inspector->getStorageName(User::class), $dbQuery->table);

        $columnCount = count($inspector->getKeys(User::class)) +  count($inspector->getKeys(Contact::class));
        $this->assertCount($columnCount, $dbQuery->columns);
        $this->assertCount(2, $dbQuery->conditions);
        $this->assertCount(1, $dbQuery->joins);

    }

    #[Test] public function select_from_user_with_parent_contact()
    {
        $query = (new OrmQuery(User::class))->with('contact', 'parent.contact');
        $inspector = $this->newInspector();

        $query->where(UserMap::EMAIL, 'LIKE', '%@outlook.com')
            ->where(UserMap::CREATED_AT, '>', new DateTime('2020-04-15 00:00:00'));

        $dbQuery = $this->queryBuilder($inspector)->toSelect($query);

        $this->assertInstanceOf(QueryContract::class, $dbQuery);
        $this->assertEquals($inspector->getStorageName(User::class), $dbQuery->table);

        $columnCount = count($inspector->getKeys(User::class)) +  count($inspector->getKeys(Contact::class));
        $this->assertCount($columnCount*2, $dbQuery->columns);
        $this->assertCount(2, $dbQuery->conditions);
        $this->assertCount(3, $dbQuery->joins);

    }

    #[Test] public function select_from_user_with_tokens()
    {
        $query = (new OrmQuery(User::class))->with('tokens');
        $inspector = $this->newInspector();

        $query->where(UserMap::EMAIL, 'LIKE', '%@outlook.com')
            ->where(UserMap::CREATED_AT, '>', new DateTime('2020-04-15 00:00:00'));

        /** @var Query $dbQuery */
        $dbQuery = $this->queryBuilder($inspector)->toSelect($query);

        $this->assertInstanceOf(QueryContract::class, $dbQuery);
        $this->assertEquals($inspector->getStorageName(User::class), $dbQuery->table);

        $columnCount = count($inspector->getKeys(User::class));

        $this->assertCount($columnCount, $dbQuery->columns);
        $this->assertCount(2, $dbQuery->conditions);
        $this->assertCount(1, $dbQuery->joins);

        $toManyQuery = $dbQuery->getAttached(OrmQueryBuilder::TO_MANY);

        $columnCount = count($inspector->getKeys(Token::class)) + 1;

        $this->assertCount($columnCount, $toManyQuery->columns);
        $this->assertCount(2, $toManyQuery->conditions);
        $this->assertCount(1, $toManyQuery->joins);

    }

    #[Test] public function select_from_user_with_to_many_that_has_to_one()
    {
        $query = (new OrmQuery(User::class))->with('projects.type', 'projects.files');
        $inspector = $this->newInspector();

        $query->where(UserMap::EMAIL, 'LIKE', '%@outlook.com')
            ->where('projects.type.'.ProjectTypeMap::NAME, 'like', '%sports%');

        $dbQuery = $this->queryBuilder($inspector)->toSelect($query);

        $this->assertInstanceOf(QueryContract::class, $dbQuery);
        $this->assertEquals($inspector->getStorageName(User::class), $dbQuery->table);

        $columnCount = count($inspector->getKeys(User::class));

        $this->assertCount($columnCount, $dbQuery->columns);
        $this->assertCount(2, $dbQuery->conditions);
        $this->assertCount(4, $dbQuery->joins);

        $toManyQuery = $dbQuery->getAttached(OrmQueryBuilder::TO_MANY);

        $columnCount = count($inspector->getKeys(Project::class)) +
            count($inspector->getKeys(ProjectType::class)) +
            count($inspector->getKeys(File::class)) + 1;

        $this->assertCount($columnCount, $toManyQuery->columns);
        $this->assertCount(2, $toManyQuery->conditions);
        $this->assertCount(4, $toManyQuery->joins);

    }

    #[Test] public function select_from_user_with_to_one_and_to_many_that_has_to_one()
    {
        $query = (new OrmQuery(User::class))->with('projects.type', 'projects.files', 'contact', 'parent');
        $inspector = $this->newInspector();

        $query->where(UserMap::EMAIL, 'LIKE', '%@outlook.com')
            ->where('projects.type.'.ProjectTypeMap::NAME, 'like', '%sports%');

        $dbQuery = $this->queryBuilder($inspector)->toSelect($query);

        $this->assertInstanceOf(QueryContract::class, $dbQuery);
        $this->assertEquals($inspector->getStorageName(User::class), $dbQuery->table);

        $columnCount = count($inspector->getKeys(User::class)) +
            count($inspector->getKeys(Contact::class)) +
            count($inspector->getKeys(User::class));

        $this->assertCount($columnCount, $dbQuery->columns);
        $this->assertCount(2, $dbQuery->conditions);
        $this->assertCount(6, $dbQuery->joins);

        $toManyQuery = $dbQuery->getAttached(OrmQueryBuilder::TO_MANY);

        $columnCount = count($inspector->getKeys(Project::class)) +
            count($inspector->getKeys(ProjectType::class)) +
            count($inspector->getKeys(File::class)) + 1;

        $this->assertCount($columnCount, $toManyQuery->columns);
        $this->assertCount(2, $toManyQuery->conditions);
        $this->assertCount(6, $toManyQuery->joins);

    }

}