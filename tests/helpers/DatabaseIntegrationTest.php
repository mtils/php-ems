<?php
/**
 *  * Created by mtils on 19.04.20 at 10:10.
 **/

namespace Ems;


use DateTime;
use DateTimeInterface;
use Ems\Contracts\Model\Database\Connection;
use Ems\Core\Filesystem\CsvReadIterator;
use Ems\Core\Url;
use Ems\Model\Database\Dialects\SQLiteDialect;
use Ems\Model\Database\PDOConnection;

use function crc32;
use function file_get_contents;

class DatabaseIntegrationTest extends IntegrationTest
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

        $tables = [
            'contacts',
            'users',
            'tokens',
            'groups',
            'user_group',
            'project_types',
            'files',
            'projects',
            'project_file'
        ];

        foreach ($tables as $basename) {
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