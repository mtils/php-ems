<?php
/**
 *  * Created by mtils on 21.03.20 at 14:23.
 **/

namespace Ems\Model\Database;


use DateTime;
use DateTimeInterface;
use Ems\Contracts\Model\Database\Connection;
use Ems\Contracts\Model\Database\Dialect;
use Ems\Core\Expression;
use Ems\Core\Filesystem\CsvReadIterator;
use Ems\Core\Support\GenericRenderer;
use Ems\Core\Url;
use Ems\DatabaseIntegrationTest;
use Ems\IntegrationTest;
use Ems\Model\Database\Dialects\SQLiteDialect;

use Ems\Pagination\Paginator;
use Ems\TestData;

use function crc32;
use function file_get_contents;
use function iterator_to_array;
use function print_r;

class QueryIntegrationTest extends DatabaseIntegrationTest
{

    /**
     * @var Connection
     */
    protected static $con;

    /**
     * @var array
     */
    protected static $data = [];

    /**
     * @var DateTime
     */
    protected static $created_at;

    /**
     * @var DateTime
     */
    protected static $updated_at;

    /**
     * @var array
     */
    protected static $contactKeys = ['first_name', 'last_name', 'company', 'city', 'county', 'postal', 'phone1', 'phone2', 'created_at', 'updated_at'];

    /**
     * @var array
     */
    protected static $userKeys = ['email', 'password', 'web', 'created_at', 'updated_at'];

    /**
     * @test
     */
    public function it_can_be_instantiated()
    {
        $this->assertInstanceOf(Query::class, static::$con->query());
    }

    /**
     * @test
     */
    public function mimeType_is_sql()
    {
        $this->assertEquals('application/sql', static::$con->query()->mimeType());
    }

    /**
     * @test
     */
    public function select_all_entries()
    {

        $count = 0;
        $query = static::$con->query('users');
        $query->join('contacts')->on('users.contact_id', 'contacts.id');

        foreach($query as $row) {
            $data = static::$data[$row['id']];
            $this->assertSameUser($data, $row);
            $this->assertSameContact($data, $row);
            $count++;
        }
        $this->assertEquals(count(static::$data), $count);

    }

    /**
     * @test
     */
    public function select_paginated()
    {

        $perPage = 10;
        $query = static::$con->query('users');
        $query->join('contacts')->on('users.contact_id', 'contacts.id');

        foreach ([1,2,3] as $page) {

            $rows = [];

            $items = $query->orderBy('id')->paginate($page, $perPage);
            $this->assertInstanceOf(Paginator::class, $items);

            foreach($items as $row) {
                $data = static::$data[$row['id']];
                $rows[] = $row;
                $this->assertSameUser($data, $row);
                $this->assertSameContact($data, $row);
            }

            $this->assertEquals(count(static::$data), $items->getTotalCount());
            $this->assertEquals($perPage, count($rows));

            $first = $items->first();
            $this->assertSameUser(static::$data[$first['id']], $first);
            $this->assertSameContact(static::$data[$first['id']], $first);

            $last = $items->last();
            $this->assertSameUser(static::$data[$last['id']], $last);
            $this->assertSameContact(static::$data[$last['id']], $last);

        }

    }

    /**
     * @test
     */
    public function select_paginated_with_custom_count_query()
    {

        $perPage = 10;
        $query = static::$con->query('users');

        $countQuery = clone $query;
        $countQuery->offset(null)->limit(null);
        $countQuery->columns = [];
        $countQuery->orderBys = [];
        $countQuery->select(new Expression('250 as total'));

        $this->assertSame($query, $query->setCountQuery($countQuery));
        $this->assertSame($countQuery, $query->getCountQuery());


        $query->join('contacts')->on('users.contact_id', 'contacts.id');

        $items = $query->orderBy('id')->paginate(1, $perPage);
        $this->assertInstanceOf(Paginator::class, $items);
        $this->assertSame(250, $items->getTotalCount());

    }

    /**
     * @test
     */
    public function select_paginated_without_paginator()
    {

        $perPage = 10;
        $query = static::$con->query('users');
        $query->join('contacts')->on('users.contact_id', 'contacts.id');

        Query::$paginatorClassExists = false;
        foreach ([1,2,3] as $page) {

            $rows = [];

            $items = $query->orderBy('id')->paginate($page, $perPage);
            $this->assertInstanceOf(PDOResult::class, $items);

            foreach($items as $row) {
                $data = static::$data[$row['id']];
                $rows[] = $row;
                $this->assertSameUser($data, $row);
                $this->assertSameContact($data, $row);
            }

            $this->assertEquals($perPage, count($rows));

            $first = $items->first();
            $this->assertSameUser(static::$data[$first['id']], $first);
            $this->assertSameContact(static::$data[$first['id']], $first);

            $last = $items->last();
            $this->assertSameUser(static::$data[$last['id']], $last);
            $this->assertSameContact(static::$data[$last['id']], $last);

        }
        Query::$paginatorClassExists = true;

    }

    /**
     * @test
     */
    public function select_paginated_with_custom_paginator()
    {

        $perPage = 10;
        $query = static::$con->query('users');
        $query->join('contacts')->on('users.contact_id', 'contacts.id');

        $query->createPaginatorBy(function ($result, $query, $page, $perPage) {
            return [
                'result' => $result,
                'query'  => $query,
                'page'   => $page,
                'perPage'=> $perPage
            ];
        });

        foreach ([1,2,3] as $page) {

            $rows = [];

            $result = $query->orderBy('id')->paginate($page, $perPage);
            $items = $result['result'];
            $this->assertInstanceOf(PDOResult::class, $items);
            $this->assertSame($query, $result['query']);
            $this->assertEquals($page, $result['page']);
            $this->assertEquals($perPage, $result['perPage']);

            foreach($items as $row) {
                $data = static::$data[$row['id']];
                $rows[] = $row;
                $this->assertSameUser($data, $row);
                $this->assertSameContact($data, $row);
            }

            $this->assertEquals($perPage, count($rows));

            $first = $items->first();
            $this->assertSameUser(static::$data[$first['id']], $first);
            $this->assertSameContact(static::$data[$first['id']], $first);

            $last = $items->last();
            $this->assertSameUser(static::$data[$last['id']], $last);
            $this->assertSameContact(static::$data[$last['id']], $last);

        }
        Query::$paginatorClassExists = true;

    }

    /**
     * @test
     */
    public function replace_inserts_or_updates()
    {
        $query = static::$con->query('contacts');

        $firstName = 'Reed';
        $lastName = 'Weisinger';

        $newLastName = 'Weisinger2';
        $newFirstName = 'Reed2';

        $users = $query->where('last_name', $lastName)
                    ->where('first_name', $firstName);

        $found = iterator_to_array($users);
        $this->assertCount(1, $found);
        $this->assertEquals($lastName, $found[0]['last_name']);

        $affected = static::$con->query('contacts')->replace([
            'id'            => $found[0]['id'],
            'last_name'     => $newLastName,
            'first_name'    => $newFirstName,
            'created_at'    => new DateTime(),
            'updated_at'    => new DateTime(),
        ]);

        $this->assertSame(1, $affected);

        $users = static::$con->query('contacts')->where('last_name', $lastName)
            ->where('first_name', $firstName);

        $found = iterator_to_array($users);
        $this->assertCount(0, $found);

        $maxId = static::$con->query('contacts')
            ->select(new Expression('MAX(id) AS max_id'))
            ->first()['max_id'];

        $this->assertEquals(500, $maxId);

        $nextId = (int)$maxId+1;

        $affected = static::$con->query('contacts')->replace([
             'id'            => $nextId,
             'last_name'     => $lastName,
             'first_name'    => $firstName,
             'created_at'    => new DateTime(),
             'updated_at'    => new DateTime(),
         ]);

        $this->assertSame(1, $affected);

        $users = static::$con->query('contacts')
            ->where('last_name', $lastName)
            ->where('first_name', $firstName);

        $found = iterator_to_array($users);
        $this->assertCount(1, $found);

    }

    /**
     * @test
     */
    public function update_changes_record()
    {
        $user = static::$con->query('contacts')
            ->where('last_name', 'Ear')
            ->where('first_name', 'Luis')
            ->first();

        $this->assertEquals('Luis', $user['first_name']);
        $this->assertEquals('Ear', $user['last_name']);
        $this->assertEquals('Whittington', $user['city']);
        $this->assertEquals('Shropshire', $user['county']);

        $affected = static::$con->query('contacts')
            ->where('id', $user['id'])
            ->update([
                'city'      => 'Köln',
                'county'    => 'Nordrhein Westfalen'
            ]);

        $this->assertSame(1, $affected);

        $user = static::$con->query('contacts')
            ->where('last_name', 'Ear')
            ->where('first_name', 'Luis')
            ->first();

        $this->assertEquals('Luis', $user['first_name']);
        $this->assertEquals('Ear', $user['last_name']);
        $this->assertEquals('Köln', $user['city']);
        $this->assertEquals('Nordrhein Westfalen', $user['county']);

    }

    /**
     * @test
     */
    public function delete_removes_record()
    {
        $lastName = 'Weisinger2';
        $firstName = 'Reed2';


        $user = static::$con->query('contacts')
            ->where('last_name', $lastName)
            ->where('first_name', $firstName)
            ->first();

        $this->assertEquals($lastName, $user['last_name']);

        $affected = static::$con->query('contacts')
            ->where('last_name', $lastName)
            ->where('first_name', $firstName)
        ->delete();

        $this->assertSame(1, $affected);

        $this->assertNull(static::$con->query('contacts')
            ->where('last_name', $lastName)
            ->where('first_name', $firstName)
            ->first());
    }

    /**
     * @test
     */
    public function insert_creates_record()
    {
        $query = static::$con->query('contacts');

        $firstName = 'Michael';
        $lastName = 'Tils';

        $users = $query->where('last_name', $lastName)
            ->where('first_name', $firstName);

        $found = iterator_to_array($users);
        $this->assertCount(0, $found);

        $insertedId = static::$con->query('contacts')->insert(
            [
                'last_name' => $lastName,
                'first_name' => $firstName,
                'created_at' => new DateTime(),
                'updated_at' => new DateTime(),
            ]
        );

        $this->assertGreaterThan(500, $insertedId);


        $users = static::$con->query('contacts')
            ->where('last_name', $lastName)
            ->where('first_name', $firstName);

        $found = iterator_to_array($users);
        $this->assertCount(1, $found);

        $this->assertEquals($firstName, $found[0]['first_name']);
        $this->assertEquals($lastName, $found[0]['last_name']);
    }

    /**
     * @test
     */
    public function select_with_unprepared_query()
    {
        $query = static::$con->query('contacts');
        $renderer = $query->getRenderer();

        $proxy = new GenericRenderer(function ($query) use ($renderer) {
            $expression = $renderer->render($query);
            return SQL::render($expression->toString(), $expression->getBindings());
        });

        $query->setRenderer($proxy);

        $query->where('last_name', 'like', 'C%');

        $count = 0;
        foreach ($query as $row) {
            if ($row['last_name'][0] != 'C') {
                $this->fail('Not matching last name found');
            }
            $count++;
        }

        $this->assertGreaterThan(1, $count);
    }

    /**
     * @test
     */
    public function insert_with_unprepared_query()
    {
        $query = static::$con->query('contacts');

        $firstName = 'Michaela';
        $lastName = 'Tils';

        $users = $query->where('last_name', $lastName)
            ->where('first_name', $firstName);

        $found = iterator_to_array($users);
        $this->assertCount(0, $found);

        $insertQuery = static::$con->query('contacts');
        $renderer = $query->getRenderer();

        $proxy = new GenericRenderer(function ($query) use ($renderer) {
            $expression = $renderer->render($query);
            return SQL::render($expression->toString(), $expression->getBindings());
        });

        $this->assertTrue($proxy->canRender($insertQuery));

        $insertQuery->setRenderer($proxy);

        $insertedId = $insertQuery->insert(
            [
                'last_name' => $lastName,
                'first_name' => $firstName,
                'created_at' => new DateTime(),
                'updated_at' => new DateTime(),
            ]
        );

        $this->assertGreaterThan(500, $insertedId);


        $users = static::$con->query('contacts')
            ->where('last_name', $lastName)
            ->where('first_name', $firstName);

        $found = iterator_to_array($users);
        $this->assertCount(1, $found);

        $this->assertEquals($firstName, $found[0]['first_name']);
        $this->assertEquals($lastName, $found[0]['last_name']);
    }

    protected static function assertSameUser($csv, $database)
    {
        $expected = static::datesToStrings(static::only(static::$userKeys, $csv));
        $test = static::only(static::$userKeys, $database);

        static::assertEquals($expected, $test, 'The user data did not match');
    }

    protected static function assertSameContact($csv, $database)
    {
        $expected = static::datesToStrings(static::only(static::$contactKeys, $csv));
        $test = static::only(static::$contactKeys, $database);

        static::assertEquals($expected, $test, 'The contact data did not match');
    }

}