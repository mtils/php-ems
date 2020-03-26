<?php
/**
 *  * Created by mtils on 21.03.20 at 14:23.
 **/

namespace Ems\Model\Database;


use DateTime;
use DateTimeInterface;
use Ems\Contracts\Model\Database\Connection;
use Ems\Contracts\Model\Database\Dialect;
use Ems\Core\Filesystem\CsvReadIterator;
use Ems\Core\Url;
use Ems\IntegrationTest;
use Ems\Model\Database\Dialects\SQLiteDialect;

use Ems\Pagination\Paginator;
use Ems\TestData;

use function crc32;
use function file_get_contents;
use function iterator_to_array;
use function print_r;

class QueryIntegrationTest extends IntegrationTest
{
    use TestData;

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
     * @beforeClass
     */
    public static function createDatabase()
    {
        $dialect = new SQLiteDialect();
        static::$con = new PDOConnection(new Url('sqlite://memory'));
        static::$con->setDialect($dialect);
        static::createSchema(static::$con);
        static::$created_at = (new DateTime())->modify('-1 day');
        static::$updated_at = new DateTime();
        static::loadData(static::dataFile('sample-contacts-500.csv'));
        static::fillDatabase(static::$con, static::$data);
    }

    /**
     * @afterClass
     */
    public static function destroyDatabase()
    {
        static::$con->close();
    }

    protected static function createSchema(Connection $con)
    {
        $schemaDir = static::dirOfTests('database/schema');

        foreach (['contacts','users', 'tokens','groups','user_group'] as $basename) {
            $con->write(file_get_contents("$schemaDir/$basename.sql"));
        }

    }

    protected static function loadData($file)
    {
        $reader = new CsvReadIterator($file);
        $id = 1;
        foreach ($reader as $row) {
            $row['password'] = crc32($row['web']);
            $row['created_at'] = static::$created_at;
            $row['updated_at'] = static::$updated_at;
            static::$data[$id] = $row;
            $id++;
        }
    }

    protected static function fillDatabase(Connection $con, array $data)
    {
        foreach($data as $i=>$row) {

            $contactData = static::only(
                ['first_name', 'last_name', 'company', 'city', 'county', 'postal', 'phone1', 'phone2', 'created_at', 'updated_at'],
                $row
            );

            $contactId = $con->query('contacts')->insert($contactData, true);

            $userData = static::only(
                ['email', 'password', 'web', 'created_at', 'updated_at'],
                $row
            );

            $userData['contact_id'] = $contactId;

            $con->query('users')->insert($userData, false);
        }
    }

    protected static function only(array $keys, array $data)
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = isset($data[$key]) ? $data[$key] : null;
        }
        return $result;
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

    /**
     * @param array $data
     *
     * @return array
     */
    protected static function datesToStrings(array $data)
    {
        $format = static::$con->dialect()->timestampFormat();
        $casted = [];
        foreach ($data as $key=>$value) {
            $casted[$key] = $value instanceof DateTimeInterface ? $value->format($format) : $value;
        }
        return $casted;
    }
}