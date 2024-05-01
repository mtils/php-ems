<?php


namespace Ems\Model\Database;

use Ems\Contracts\Expression\Prepared;
use Ems\Contracts\Model\Database\Connection;
use Ems\Contracts\Model\Database\Dialect;
use Ems\Contracts\Model\Database\NativeError;
use Ems\Contracts\Model\Database\SQLException;
use Ems\Contracts\Model\Result;
use Ems\Core\Url;
use Ems\Testing\Cheat;
use Exception;
use InvalidArgumentException;
use PDOException;

use function call_user_func;
use function key;
use function range;

require_once(__DIR__.'/PDOBaseTest.php');

class PDOConnectionTest extends PDOBaseTest
{

    public function test_implements_interface()
    {
        $this->assertInstanceof(
            Connection::class,
            $this->newConnection(false)
        );
    }

    public function test_select_empty_result()
    {
        $con = $this->newConnection();
        $result = $con->select('SELECT * FROM users');
        $this->assertInstanceof(Result::class, $result);
        $rows = [];
        foreach ($result as $row) {
            $rows[] = $row;
        }

        $this->assertCount(0, $rows);
    }

    public function test_insert()
    {

        $con = $this->newConnection();

        $q = 'INSERT INTO users (login,age,weight) VALUES (?,?,?)';

        $this->assertEquals(1, $con->insert($q, ['sabine', 44, 84.3]));
        $this->assertEquals(2, $con->insert($q, ['helmut', 76, 75.4]));
        $this->assertEquals(3, $con->insert($q, ['susanne', 22, 68.7]));

    }

    public function test_dialect_returns_dialect()
    {
        $this->assertEquals('sqlite', $this->newConnection()->dialect());
    }

    public function test_dialect_returns_setted()
    {
        $con = $this->newConnection()->setDialect('mysql');
        $this->assertEquals('mysql', $con->dialect());
    }

    public function test_setDialect_throws_exception_on_unsupported_value()
    {
        $this->expectException(InvalidArgumentException::class);
        $con = $this->newConnection()->setDialect(131);
    }

    public function test_insert_and_select()
    {

        $con = $this->newConnection();

        $q = 'INSERT INTO users (login,age,weight) VALUES (?,?,?)';

        $testData = [
            [ 'id' => 1, 'login' => 'sabine', 'age' => 44, 'weight' => 84.3],
            [ 'id' => 2, 'login' => 'helmut', 'age' => 76, 'weight' => 75.4],
            [ 'id' => 3, 'login' => 'susanne', 'age' => 22, 'weight' => 68.7]
        ];

        $this->assertNull($con->insert($q, ['sabine', 44, 84.3], false));
        $this->assertNull($con->insert($q, ['helmut', 76, 75.4], false));
        $this->assertNull($con->insert($q, ['susanne', 22, 68.7], false));

        foreach($con->select('SELECT * FROM users ORDER BY id') as $i=>$user) {
            $this->assertEquals($testData[$i], $user);
        }

    }

    public function test_update_returns_affected_rows_if_should()
    {

        $con = $this->newConnection();

        $q = 'INSERT INTO users (login,age,weight) VALUES (?,?,?)';

        $testData = [
            [ 'id' => 1, 'login' => 'sabine', 'age' => 44, 'weight' => 84.3],
            [ 'id' => 2, 'login' => 'dieter', 'age' => 81, 'weight' => 75.4],
            [ 'id' => 3, 'login' => 'sarah', 'age' => 15, 'weight' => 68.7]
        ];

        $this->assertNull($con->insert($q, ['sabine', 44, 84.3], false));
        $this->assertNull($con->insert($q, ['helmut', 76, 75.4], false));
        $this->assertNull($con->insert($q, ['susanne', 22, 68.7], false));

        $q = 'UPDATE users SET login=?, age=? WHERE id=?';

        $this->assertEquals(1, $con->write($q, ['dieter', 81, 2]));
        $this->assertNull($con->write($q, ['sarah', 15, 3], false));

        foreach($con->select('SELECT * FROM users ORDER BY id') as $i=>$user) {
            $this->assertEquals($testData[$i], $user);
        }

    }

    public function test_insert_and_select_with_begin_and_commit()
    {

        $con = $this->newConnection();

        $q = 'INSERT INTO users (login,age,weight) VALUES (?,?,?)';

        $testData = [
            [ 'id' => 1, 'login' => 'sabine', 'age' => 44, 'weight' => 84.3],
            [ 'id' => 2, 'login' => 'helmut', 'age' => 76, 'weight' => 75.4],
            [ 'id' => 3, 'login' => 'susanne', 'age' => 22, 'weight' => 68.7]
        ];

        $con->begin();
        $this->assertNull($con->insert($q, ['sabine', 44, 84.3], false));
        $this->assertNull($con->insert($q, ['helmut', 76, 75.4], false));
        $this->assertNull($con->insert($q, ['susanne', 22, 68.7], false));
        $this->assertTrue($con->isInTransaction());
        $con->commit();

        foreach($con->select('SELECT * FROM users ORDER BY id') as $i=>$user) {
            $this->assertEquals($testData[$i], $user);
        }

    }

    public function test_two_begins_throws_right_exception()
    {

        $con = $this->newConnection();

        $q = 'INSERT INTO users (login,age,weight) VALUES (?,?,?)';

        $testData = [
            [ 'id' => 1, 'login' => 'sabine', 'age' => 44, 'weight' => 84.3],
            [ 'id' => 2, 'login' => 'helmut', 'age' => 76, 'weight' => 75.4],
            [ 'id' => 3, 'login' => 'susanne', 'age' => 22, 'weight' => 68.7]
        ];

        try {
            $con->begin();
            $con->begin();
            $this->fail('To begins should fail.');
        } catch (SQLException $e) {
            $this->assertContains('transaction', $e->nativeMessage());
            $this->assertInstanceOf(NativeError::class, $e->nativeError());
        }

    }

    public function test_insert_and_select_with_begin_and_rollback()
    {

        $con = $this->newConnection();

        $q = 'INSERT INTO users (login,age,weight) VALUES (?,?,?)';

        $testData = [
            [ 'id' => 1, 'login' => 'sabine', 'age' => 44, 'weight' => 84.3],
            [ 'id' => 2, 'login' => 'helmut', 'age' => 76, 'weight' => 75.4],
            [ 'id' => 3, 'login' => 'susanne', 'age' => 22, 'weight' => 68.7]
        ];

        $con->begin();
        $this->assertNull($con->insert($q, ['sabine', 44, 84.3], false));
        $this->assertNull($con->insert($q, ['helmut', 76, 75.4], false));
        $this->assertNull($con->insert($q, ['susanne', 22, 68.7], false));
        $this->assertTrue($con->isInTransaction());
        $con->rollback();

        $rows = [];

        foreach($con->select('SELECT * FROM users ORDER BY id') as $i=>$user) {
            $rows[$i] = $user;
        }

        $this->assertEmpty($rows);

    }

    public function test_insert_and_select_with_transaction()
    {

        $con = $this->newConnection();

        $q = 'INSERT INTO users (login,age,weight) VALUES (?,?,?)';

        $testData = [
            [ 'id' => 1, 'login' => 'sabine', 'age' => 44, 'weight' => 84.3],
            [ 'id' => 2, 'login' => 'helmut', 'age' => 76, 'weight' => 75.4],
            [ 'id' => 3, 'login' => 'susanne', 'age' => 22, 'weight' => 68.7]
        ];

        $result = $con->transaction(function ($con) use ($q) {

            $con->insert($q, ['sabine', 44, 84.3]);
            $con->insert($q, ['helmut', 76, 75.4]);
            $con->insert($q, ['susanne', 22, 68.7]);
            $this->assertTrue($con->isInTransaction());

            return true;

        });

        $this->assertTrue($result);

        foreach($con->select('SELECT * FROM users WHERE id > ? ORDER BY id', [0]) as $i=>$user) {
            $this->assertEquals($testData[$i], $user);
        }

    }

    public function test_insert_and_select_with_failing_transaction()
    {

        $con = $this->newConnection();

        $q = 'INSERT INTO users (login,age,weight) VALUES (?,?,?)';

        $failing = 'INSERT INTO users (login,age,weight) VALUES (?,?,?)';

        $testData = [
            [ 'id' => 1, 'login' => 'sabine', 'age' => 44, 'weight' => 84.3],
            [ 'id' => 2, 'login' => 'helmut', 'age' => 76, 'weight' => 75.4],
            [ 'id' => 3, 'login' => 'susanne', 'age' => 22, 'weight' => 68.7]
        ];

        try {
            $result = $con->transaction(function ($con) use ($q, $failing) {

                $con->insert($q, ['sabine', 44, 84.3]);
                $con->insert($q, ['helmut', 76, 75.4]);
                $this->assertTrue($con->isInTransaction());
                $con->insert($failing, ['helmut', 22, 68.7]);

            });
        } catch (\Exception $e) {

        }


        $rows = [];

        foreach($con->select('SELECT * FROM users ORDER BY id') as $i=>$user) {
            $rows[$i] = $user;
        }

        $this->assertEmpty($rows);

    }

    public function test_insert_and_select_with_pdo_failing_transaction()
    {

        $con = $this->newConnection();

        $q = 'INSERT INTO users (login,age,weight) VALUES (?,?,?)';

        $failing = 'INSERT INTO users (login,age,weight) VALUES (?,?,?) ERROR';

        $testData = [
            [ 'id' => 1, 'login' => 'sabine', 'age' => 44, 'weight' => 84.3],
            [ 'id' => 2, 'login' => 'helmut', 'age' => 76, 'weight' => 75.4],
            [ 'id' => 3, 'login' => 'susanne', 'age' => 22, 'weight' => 68.7]
        ];

        $result = $con->transaction(function ($con) use ($q, $failing) {

            $con->insert($q, ['sabine', 44, 84.3]);
            $con->insert($q, ['helmut', 76, 75.4]);
            $this->assertTrue($con->isInTransaction());
            throw new SQLLockException('Locked', $q);

        });

        $this->assertFalse($result);

        $rows = [];

        foreach($con->select('SELECT * FROM users ORDER BY id') as $i=>$user) {
            $rows[$i] = $user;
        }

        $this->assertEmpty($rows);

    }

    public function test_transaction_with_invalid_attempts_throws_exception()
    {
        $this->expectException(InvalidArgumentException::class);

        $con = $this->newConnection();

        $q = 'INSERT INTO users (login,age,weight) VALUES (?,?,?)';

        $con->transaction(function ($con) use ($q) {
            $con->insert($q, ['sabine', 44, 84.3]);
        }, 0);

    }

    public function test_isInTransaction_returns_false_if_not_connected()
    {
        $con = $this->newConnection(false);
        $this->assertFalse($con->isInTransaction());
    }

    public function test_close_closes_connection()
    {

        $con = $this->newConnection();

        $q = 'INSERT INTO users (login,age,weight) VALUES (?,?,?)';

        $this->assertEquals(1, $con->insert($q, ['sabine', 44, 84.3]));
        $this->assertEquals(2, $con->insert($q, ['helmut', 76, 75.4]));
        $this->assertEquals(3, $con->insert($q, ['susanne', 22, 68.7]));

        $this->assertTrue($con->isOpen());
        $this->assertSame($con, $con->close());
        $this->assertFalse($con->isOpen());
    }

    public function test_prepare_with_insert_and_select()
    {

        $testData = [
            [ 'id' => 1, 'login' => 'sabine', 'age' => 44, 'weight' => 84.3],
            [ 'id' => 2, 'login' => 'helmut', 'age' => 76, 'weight' => 75.4],
            [ 'id' => 3, 'login' => 'susanne', 'age' => 22, 'weight' => 68.7]
        ];

        $con = $this->newConnection();

        $q = 'INSERT INTO users (login,age,weight) VALUES (?,?,?)';

        $statement = $con->prepare('INSERT INTO users (login,age,weight) VALUES (?,?,?)');

        $this->assertInstanceOf(Prepared::class, $statement);
        $this->assertEquals(1, $statement->write(['sabine', 44, 84.3]));
        $this->assertNull($statement->write(['helmut', 76, 75.4], false));
        $this->assertEquals(1, $statement->write(['susanne', 22, 68.7]));

        $rows = [];

        foreach($con->prepare('SELECT * FROM users ORDER BY id') as $i=>$user) {
            $rows[] = $user;
        }

        $this->assertEquals($testData, $rows);
        $this->assertEquals($q, $statement->query());

    }

    public function test_exception_is_converted_by_dialect()
    {
        $dialect = $this->mock(Dialect::class);

        $con = $this->newConnection();

        $this->injectDialect($con, $dialect);

        $exception = new SQLException('bla');

        $dialect->shouldReceive('createException')
                ->atLeast()->once()
                ->andReturn($exception);

        try {
            $con->commit();
            $this->fail('Commit without a begin should fail');
        } catch (SQLException $e) {
            $this->assertSame($exception, $e);
        }
//         $this->assertEquals('mysql', $con->dialect());
    }

    public function test_methodsHooks_contains_basic_hooks()
    {
        $con = $this->newConnection();
        $hooks = $con->methodHooks();
        foreach (['select', 'insert', 'write', 'prepare'] as $hook) {
            $this->assertContains($hook, $hooks);
        }
    }

    public function test_url_returns_url()
    {
        $url = new Url('mysql://user@host/phpmyadmin');
        $con = $this->newConnection(false, $url);
        $this->assertSame($url, $con->url());
    }

    public function test_urlToDsn_creates_valid_dsn()
    {
        $url = new Url('mysql://username@hostname:3363/phpmyadmin');
        $con = $this->newConnection(false, $url);

        $dsn = Cheat::call($con, 'urlToDsn', [$url]);

        $this->assertEquals('mysql:dbname=phpmyadmin;host=hostname;port=3363', $dsn);

    }

    public function test_reconnect_if_dropped()
    {
        $con = new PDOConnectionTest_PDOConnection(new Url('sqlite://memory'));
        $this->createTable($con);

        $result = $con->select('SELECT 1')->first();
        $this->assertEquals('1', $result[key($result)]);

        $tries = [];
        $maxTries = (int)$con->getOption(PDOConnection::AUTO_RECONNECT_TRIES);

        $con->runner = function (callable $run, $query='') use ($maxTries, &$tries) {
            static $try = 0;
            $try++;

            $tries[] = $try;

            if ($try < $maxTries) {
                throw new PDOException('MySQL server has gone away.');
            }

            $e = new PDOConnectionTest_UseOriginalException();
            $e->run = $run;
            $e->query = $query;
            throw $e;

        };

        $result = $con->select('SELECT 1')->first();
        $this->assertEquals(range(1,3), $tries);
        $this->assertEquals('1', $result[key($result)]);

    }

    public function test_reconnect_gives_up_if_max_reached()
    {
        $con = new PDOConnectionTest_PDOConnection(new Url('sqlite://memory'));
        $this->createTable($con);

        $result = $con->select('SELECT 1')->first();
        $this->assertEquals('1', $result[key($result)]);

        $tries = [];
        $maxTries = (int)$con->getOption(PDOConnection::AUTO_RECONNECT_TRIES);

        $con->runner = function (callable $run, $query='') use ($maxTries, &$tries) {
            static $try = 0;
            $try++;

            $tries[] = $try;

            if ($try < $maxTries+1) {
                throw new PDOException('MySQL server has gone away.');
            }

            $e = new PDOConnectionTest_UseOriginalException();
            $e->run = $run;
            $e->query = $query;
            throw $e;

        };

        try {
            $con->select('SELECT 1')->first();
            $this->fail('The test must fail because max connection tries are exceeded.');
        } catch (SQLException $e) {
            $error = $e->nativeError();
            $this->assertInstanceOf(NativeError::class, $error);
            $this->assertContains('trying attempt #', $e->getMessage());
        }

    }

    public function test_does_not_reconnect_if_max_is_zero()
    {
        $con = new PDOConnectionTest_PDOConnection(new Url('sqlite://memory'));
        $this->createTable($con);

        $result = $con->select('SELECT 1')->first();
        $this->assertEquals('1', $result[key($result)]);

        $tries = [];
        $con->setOption(PDOConnection::AUTO_RECONNECT_TRIES, 0);

        $exception = new PDOException('MySQL server has gone away.');

        $con->runner = function (callable $run, $query='') use (&$tries, $exception) {
            static $try = 0;
            $try++;

            if ($try == 1) {
                throw $exception;
            }

            $this->fail("No reconnects should be tried if zero configured");

        };

        try {
            $con->select('SELECT 1')->first();
            $this->fail('The connection must not reconnect if tries set to zero');
        } catch (SQLException $e) {
            $this->assertSame($exception, $e->getPrevious());
        }

    }

    protected function injectDialect(PDOConnection $connection, Dialect $dialect)
    {
        $connection->setDialect($dialect);
    }
}

class PDOConnectionTest_PDOConnection extends PDOConnection
{
    /**
     * @var callable
     */
    public $runner;

    /**
     * Try to perform an operation. If it fails convert the native exception
     * into a SQLException.
     *
     * @param callable $run
     * @param string $query
     *
     * @return mixed
     */
    protected function attempt(callable $run, $query = '')
    {

        if (!$this->runner || $this->isRetry($run)) {
            return parent::attempt($run, $query);
        }

        return parent::attempt(function () use ($run, $query) {
            try {
                return call_user_func($this->runner, $run, $query);
            } catch (PDOConnectionTest_UseOriginalException $e) {
                return parent::attempt($e->run, $e->query);
            }
        }, $query);

    }

}

class PDOConnectionTest_UseOriginalException extends Exception
{
    /**
     * @var callable
     */
    public $run;

    /**
     * @var string
     */
    public $query = '';
}