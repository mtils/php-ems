<?php


namespace Ems\Model\Database\Dialects;

use DateTime;
use Ems\Contracts\Core\Errors\UnSupported;
use Ems\Core\Expression;
use Ems\Core\KeyExpression;
use Ems\Core\Url;
use Ems\Contracts\Model\Database\Dialect;
use Ems\Contracts\Model\Database\SQLException;
use Ems\Expression\ConditionGroup;
use Ems\Expression\Condition;
use Ems\Expression\Constraint;
use Ems\Model\Database\SQL;
use Ems\Model\Database\PDOBaseTest;
use Ems\Model\Database\SQLConstraintException;
use Ems\Model\Database\SQLDeniedException;
use Ems\Model\Database\SQLExceededException;
use Ems\Model\Database\SQLIOException;
use Ems\Model\Database\SQLLockException;
use Ems\Model\Database\SQLNameNotFoundException;
use Ems\Model\Database\SQLSyntaxException;
use InvalidArgumentException;

require_once(__DIR__.'/../PDOBaseTest.php');

class SQLiteDialectTest extends PDOBaseTest
{

    public function test_implements_interface()
    {
        $this->assertInstanceof(
            Dialect::class,
            $this->newDialect()
        );
    }

    public function test_quote_string()
    {
        $d = $this->newDialect();
        $this->assertEquals("'Hello'", $d->quote('Hello'));
        $this->assertEquals("'Hello you'", $d->quote('Hello you'));
        $this->assertEquals("'Hello ''you'", $d->quote('Hello \'you'));
    }

    public function test_quote_name()
    {
        $d = $this->newDialect();
        $this->assertEquals('"users"', $d->quote('users', 'name'));
        $this->assertEquals('"us""er"', $d->quote('us"er', 'name'));
    }

    public function test_quote_throws_expception_with_unknown_type()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->newDialect()->quote('foo', 'bar');
    }

    public function test_name()
    {
        $this->assertEquals('sqlite', $this->newDialect()->name());
        $this->assertEquals('sqlite', (string)$this->newDialect());
    }

    public function test_timestampFormat()
    {
        $format = $this->newDialect()->timeStampFormat();
        $this->assertEquals('Y-m-d H:i:s', $format);
    }

    public function test_render_simple_condition()
    {
        $bindings = [];
        $d = $this->newDialect();

        $c = $this->where('login', 'peter');

        $sql = $d->render($c, $bindings);

        $this->assertEquals($sql, '"login" = ?');
        $this->assertEquals($bindings, ['peter']);

        $bindings = [];

        $c = $this->where('users.login', 'peter');

        $sql = $d->render($c, $bindings);

        $this->assertEquals($sql, '"users"."login" = ?');
        $this->assertEquals($bindings, ['peter']);

    }

    public function test_render_conjunction()
    {
        $bindings = [];
        $d = $this->newDialect();

        $c = $this->where('login', 'peter')
                  ->where('age', '>', 14);


        $sql = $d->render($c, $bindings);

        $this->assertEquals('"login" = ? AND "age" > ?', $sql);
        $this->assertEquals(['peter', 14], $bindings);

    }

    public function test_render_nested_conjunction()
    {

        $birthday = new DateTime('2017-10-10 00:00:00');

        $c = $this->where('login', 'peter')
                  ->where('age', '>', 14)
                  ->orWhere(function ($c) use ($birthday) {
                    return $c->where('last_name', 'like', '%üller')
                             ->where('birthday', '<', $birthday);
                  });

        $bindings = [];
        $d = $this->newDialect();

        $sql = $d->render($c, $bindings);

        $this->assertEquals('("login" = ? AND "age" > ?) OR ("last_name" LIKE ? AND "birthday" < ?)', $sql);
        $this->assertEquals([
            'peter',
            14,
            '%üller',
            '2017-10-10 00:00:00'
        ], $bindings);

    }

    public function test_render_with_unsupported_expression_throws_exception()
    {

        $bindings = [];

        $c = $this->where('n', new SQLiteDialectTest_Expression('test'));
        $d = $this->newDialect();

        $this->assertEquals('"n" = test',  $d->render($c, $bindings));

    }

    public function test_render_without_constraint_parameters_throws_exception()
    {
        $this->expectException(Unsupported::class);

        $bindings = [];

        $c = $this->where('n', new Constraint('equals', [], '='));

        $d = $this->newDialect();

        $sql = $d->render($c, $bindings);

    }

    public function test_render_with_unsupported_operator_throws_exception()
    {
        $this->expectException(Unsupported::class);

        $bindings = [];

        $c = $this->where('n', 'foo', 'bar');
        $d = $this->newDialect();

        $sql = $d->render($c, $bindings);

    }

    public function test_render_condition_without_constraint()
    {

        $bindings = [];

        $c = $this->where(new Condition(1));

        $d = $this->newDialect();

        $sql = $d->render($c, $bindings);

        $this->assertEquals('?', $sql);
        $this->assertEquals([1], $bindings);

    }

    public function test_render_null_condition()
    {

        $bindings = [];

        $c = $this->where('updated_at', 'is', null);

        $d = $this->newDialect();

        $sql = $d->render($c, $bindings);

        $this->assertEquals('"updated_at" IS NULL', $sql);
        $this->assertEquals([], $bindings);

        $bindings = [];

        $c = $this->where('updated_at', null);

        $d = $this->newDialect();

        $sql = $d->render($c, $bindings);

        $this->assertEquals('"updated_at" IS NULL', $sql);
        $this->assertEquals([], $bindings);

    }

    public function test_render_in_operator()
    {

        $bindings = [];

        $c = $this->where('updated_at', 'in', [1,2,3]);

        $d = $this->newDialect();

        $sql = $d->render($c, $bindings);

        $this->assertEquals('"updated_at" IN (?,?,?)', $sql);
        $this->assertEquals([1,2,3], $bindings);

        $bindings = [];

        $c = $this->where('updated_at', [3,4,5,6]);

        $d = $this->newDialect();

        $sql = $d->render($c, $bindings);

        $this->assertEquals('"updated_at" IN (?,?,?,?)', $sql);
        $this->assertEquals([3,4,5,6], $bindings);

    }

    public function test_render_condition_with_expression_in_constraint()
    {

        $bindings = [];

        $c = $this->where('addresses.country_code', 'like', SQL::key('users.locale'));

        $d = $this->newDialect();

        $sql = $d->render($c, $bindings);

        $this->assertEquals('"addresses"."country_code" LIKE "users"."locale"', $sql);
        $this->assertEquals([], $bindings);

    }

    public function test_render_with_unsupported_parameter_in_constraint_throws_exception()
    {
        $this->expectException(Unsupported::class);

        $bindings = [];

        $c = $this->where('a', 'like', new \stdclass);
        $d = $this->newDialect();

        $sql = $d->render($c, $bindings);

    }

    public function test_createException_creates_SQLNotFoundException_when_table_not_found()
    {
        $con = $this->newConnection(false)->setDialect($this->newDialect());

        try {
            $con->select('SELECT * FROM addresses');
            $this->fail('select on a non existing table should fail');
        } catch (SQLNameNotFoundException $e) {
            $this->assertEquals('table', $e->missingType);
        }
    }

    public function test_createException_creates_SQLNotFoundException_when_column_not_found()
    {

        $con = $this->newConnection()->setDialect($this->newDialect());

        try {
             $con->select('SELECT foo FROM users');
             $this->fail('select on a non existing column should fail');
        } catch (SQLNameNotFoundException $e) {
             $this->assertEquals('column', $e->missingType);
         }
    }

    public function test_createException_creates_SQLAccessDeniedException_if_database_not_writeable()
    {

        $url = new Url('sqlite:///proc/test.db');

        $con = $this->newConnection(false, $url)->setDialect($this->newDialect());

        try {
             $con->select('SELECT foo FROM users');

             $this->fail('select on a non existing column should fail');
        } catch (SQLDeniedException $e) {
             $this->assertContains('unable', $e->getMessage());

        }
    }

    public function test_createException_creates_SQLLockedException_if_database_is_locked()
    {

        $url = new Url('sqlite://' . sys_get_temp_dir() . '/test.db');

        $this->removeDB($url);

        $con = $this->newConnection(true, $url)->setDialect($this->newDialect());
        $con2 = $this->newConnection(false, $url)->setDialect($this->newDialect());

        try {
             $con->begin();
             $con->insert("INSERT INTO users (login) VALUES ('michael')");

             $con2->insert("INSERT INTO users (login) VALUES ('john')");

             $this->fail('Parallel writing to a sqlite file should fail');
        } catch (SQLLockException $e) {
             $this->assertContains('locked', $e->getMessage());
             $con->rollback();
             $this->removeDB($url);

        }
    }

    public function test_createException_creates_SQLIOException_when_column_not_found()
    {

        $dbFile = sys_get_temp_dir() . '/test.db';


        $url = new Url("sqlite://$dbFile");

        $this->removeDB($url);

        $garbage = implode("\n", array_fill(0, 50, hash('sha256','hello', true)));

        file_put_contents($dbFile, $garbage);

        $con = $this->newConnection(false, $url)->setDialect($this->newDialect());

        try {
            $con->select('SELECT foo FROM users');
            $this->fail('select on a non corrupt database should fail');
        } catch (SQLIOException $e) {

        }

        $this->removeDB($url);
    }

    public function test_createException_creates_SQLConstraintException_if_inserting_duplicates_in_unique_column()
    {

        $con = $this->newConnection()->setDialect($this->newDialect());

        try {
            $con->insert("INSERT INTO users ('login', 'weight') VALUES ('dieter', '250')");
            $con->insert("INSERT INTO users ('login', 'weight') VALUES ('dieter', '250')");
            $this->fail('inserting duplicate values in a unique column should fail');
        } catch (SQLConstraintException $e) {

        }

    }

    public function test_createException_creates_SQLSyntaxException_on_invalid_query()
    {

        $con = $this->newConnection()->setDialect($this->newDialect());

        try {
            $con->insert("bogus SELECT is stupid");
            $this->fail('Firing invalid queries should fail');
        } catch (SQLSyntaxException $e) {

        }

    }

    public function test_createException_creates_basic_SQLException_if_error_unknown()
    {

        $con = $this->newConnection()->setDialect($this->newDialect());

        try {
            $con->transaction(function () { throw new \PDOException('Failure!!'); });
            $this->fail('Firing invalid queries should fail');
        } catch (SQLException $e) {
            $this->assertEquals('Failure!!', $e->nativeMessage());
        }

    }

    protected function newDialect()
    {
        return new SQLiteDialect();
    }

    protected function where($key, $op=null, $val=null)
    {
        $g = new ConditionGroup();

        if (func_num_args() == 1) {
            return $g->where($key);
        }

        if (func_num_args() == 2) {
            return $g->where($key, $op);
        }

        if (func_num_args() == 3) {
            return $g->where($key, $op, $val);
        }

    }

    protected function removeDB(Url $url)
    {
        if (file_exists("$url->path")) {
            unlink("$url->path");
        }
    }
}

class SQLiteDialectTest_Expression extends Expression
{
}
