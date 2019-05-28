<?php
/**
 *  * Created by mtils on 26.05.19 at 07:38.
 **/

namespace Ems\Model\Database\Eloquent;

use Ems\Contracts\Model\Database\Dialect;
use Ems\Core\Url;
use Ems\Model\Database\Dialects\SQLiteDialect;
use Ems\Model\Database\PDOConnection;
use Ems\Model\Database\PDOConnectionTest;
use Ems\Model\Database\SQL;
use Ems\Model\Eloquent\IlluminateConnection;
use Illuminate\Database\Connection;
use Illuminate\Database\Connectors\SQLiteConnector;
use Illuminate\Database\MySqlConnection;
use Illuminate\Database\PostgresConnection;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Database\SqlServerConnection;
use const MYSQLI_OPT_CONNECT_TIMEOUT;
use PDO;
use stdClass;

require_once(__DIR__.'/../Database/PDOConnectionTest.php');

class IlluminateConnectionTest extends PDOConnectionTest
{
    public function test_dialect_returns_setted()
    {
        $this->assertEquals(SQL::SQLITE, $this->newConnection()->dialect());
    }

    /**
     * @expectedException \Ems\Core\Exceptions\UnsupportedUsageException
     **/
    public function test_setDialect_throws_exception_on_unsupported_value()
    {
        $con = $this->newConnection()->setDialect('mysql');
    }

    public function test_url_returns_url()
    {
        $con = $this->newConnection(false);
        $url = $con->url();
        $this->assertEquals(SQL::SQLITE, $url->scheme);
        $params = $url->query;
        $this->assertEquals('1', $params['foreign_key_constraints']);
    }

    public function test_url_takes_config_url_if_setted()
    {
        $mock = $this->mock(Connection::class);
        $url = 'mysql://usr@dbserver/cms:45';
        $mock->shouldReceive('getConfig')->andReturn([
            'url' => $url
        ]);
        $con = $this->blankConnection($mock);
        $this->assertEquals($url, (string)$con->url());
    }

    public function test_url_takes_config_to_build_url()
    {
        $mock = $this->mock(Connection::class);

        $config = [
            'driver' => 'mysql',
            'url' => '',
            'host' => 'db-cluster.my-cloud',
            'port' => 3306,
            'database' => 'erp_mirror',
            'username' => 'the_user',
            'password' => 'the_password',
            'unix_socket' => 'whatever_socket',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null
        ];
        $mock->shouldReceive('getConfig')->andReturn($config);
        $con = $this->blankConnection($mock);
        $conUrl = $con->url();

        $this->assertEquals($config['driver'], $conUrl->scheme);
        $this->assertEquals($config['username'], $conUrl->user);
        $this->assertEquals($config['host'], $conUrl->host);
        $this->assertEquals($config['port'], $conUrl->port);
        $this->assertEquals($config['database'], trim($conUrl->path,'/'));
        $this->assertEquals($config['password'], $conUrl->password);
        $this->assertEquals($config['unix_socket'], $conUrl->query['unix_socket']);
    }

    public function test_dialect_passes_right_dialect_to_SQL()
    {
        $connections = [
            SQL::MY => $this->mock(MySqlConnection::class),
            SQL::POSTGRES => $this->mock(PostgresConnection::class),
            SQL::SQLITE => $this->mock(SQLiteConnection::class),
            SQL::MS => $this->mock(SqlServerConnection::class)
        ];

        foreach ($connections as $dialectName=>$connection) {
            $con = $this->blankConnection($connection);
            $con->provideDialectBy(function ($dialectName) {
                return $dialectName;
            });

            $this->assertEquals($dialectName, $con->dialect());
        }
    }

    /**
     * @expectedException \Ems\Core\Exceptions\NotImplementedException
     */
    public function test_dialect_throws_exception_if_dialect_not_known()
    {
        $connection = new IlluminateConnectionTest_Connection(function () {});
        $con = $this->blankConnection($connection);

        $con->dialect();
    }

    public function test_setOption_sets_parent_option()
    {
        $con = $this->newConnection(false);
        $con->setOption(PDOConnection::RETURN_LAST_AFFECTED, true);
        $this->assertTrue($con->getOption(PDOConnection::RETURN_LAST_AFFECTED));
    }

    /**
     * @expectedException \Ems\Core\Exceptions\UnsupportedUsageException
     */
    public function test_setOption_throws_exception_on_non_ems_option()
    {
        $con = $this->newConnection(false);
        $con->setOption(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    /**
     * @expectedException \Ems\Core\Exceptions\UnConfiguredException
     */
    public function test_make_connection_without_provider_throws_exception()
    {
        $con = $this->blankConnection();
        $con->url();
    }

    /**
     * @expectedException \UnexpectedValueException
     */
    public function test_it_throws_exception_if_provider_does_not_return_an_laravel_connection()
    {
        $con = $this->blankConnection();
        $con->provideConnectionBy(function () {
            return new stdClass();
        });
        $con->url();
    }

    /**
     * @param IlluminateConnection $connection
     * @param Dialect $dialect
     */
    protected function injectDialect(PDOConnection $connection, Dialect $dialect)
    {
        $connection->provideDialectBy(function ($dialectName) use ($dialect) {
            return $dialect;
        });
    }


    protected function newConnection($createTable=true, Url $url=null)
    {
        $connectionProvider = function () {
            $connector = new SQLiteConnector();
            $config = [
                'driver'                  => 'sqlite',
                'database'                => ':memory:',
                'prefix'                  => '',
                'foreign_key_constraints' => 1
            ];

            $pdo = $connector->connect($config);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            return new SQLiteConnection($pdo,'','',$config);
        };

        $con = new IlluminateConnection($connectionProvider);

        if ($createTable) {
            $this->createTable($con);
        }
        return $con;
    }

    protected function resetSqliteDialectFactory()
    {
        SQL::extend(SQL::SQLITE, function ($dialectParameter) {
            if ($dialectParameter instanceof Dialect) {
                return $dialectParameter;
            }
            return new SQLiteDialect();
        });
    }

    protected function blankConnection(Connection $connection=null)
    {
        if (!$connection) {
            return new IlluminateConnection();
        }
        $con = new IlluminateConnection(function () use ($connection) {
            return $connection;
        });
        return $con;
    }

}

class IlluminateConnectionTest_Connection extends Connection
{
    //
}