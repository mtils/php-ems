<?php


namespace Ems\Model\Database\Dialects;

use DateTime;
use Ems\Contracts\Model\Database\Dialect;
use Ems\Core\Expression;
use Ems\Core\Url;
use Ems\Expression\Condition;
use Ems\Expression\ConditionGroup;
use Ems\Expression\Constraint;
use Ems\Model\Database\PDOBaseTest;
use Ems\Model\Database\SQL;

require_once(__DIR__.'/../PDOBaseTest.php');

class MySQLiteDialectTest extends PDOBaseTest
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
        $this->assertEquals('"Hello"', $d->quote('Hello'));
        $this->assertEquals('"Hello you"', $d->quote('Hello you'));
        $this->assertEquals('"Hello \"you"', $d->quote('Hello "you'));
    }

    public function test_quote_name()
    {
        $d = $this->newDialect();
        $this->assertEquals('`users`', $d->quote('users', 'name'));
        $this->assertEquals('`us``er`', $d->quote('us`er', 'name'));
    }

    /**
     * @expectedException InvalidArgumentException
     **/
    public function test_quote_throws_expception_with_unknown_type()
    {
        $this->newDialect()->quote('foo', 'bar');
    }

    public function test_name()
    {
        $this->assertEquals('mysql', $this->newDialect()->name());
        $this->assertEquals('mysql', (string)$this->newDialect());
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

        $this->assertEquals($sql, '`login` = ?');
        $this->assertEquals($bindings, ['peter']);

        $bindings = [];

        $c = $this->where('users.login', 'peter');

        $sql = $d->render($c, $bindings);

        $this->assertEquals($sql, '`users`.`login` = ?');
        $this->assertEquals($bindings, ['peter']);

    }

    public function test_render_conjunction()
    {
        $bindings = [];
        $d = $this->newDialect();

        $c = $this->where('login', 'peter')
                  ->where('age', '>', 14);


        $sql = $d->render($c, $bindings);

        $this->assertEquals('`login` = ? AND `age` > ?', $sql);
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

        $this->assertEquals('(`login` = ? AND `age` > ?) OR (`last_name` LIKE ? AND `birthday` < ?)', $sql);
        $this->assertEquals([
            'peter',
            14,
            '%üller',
            '2017-10-10 00:00:00'
        ], $bindings);

    }

    /**
     * @expectedException InvalidArgumentException
     **/
    public function test_render_with_unsupported_expression_throws_exception()
    {

        $bindings = [];

        $c = $this->where('n', new MySQLDialectTest_Expression);
        $d = $this->newDialect();

        $sql = $d->render($c, $bindings);

    }

    /**
     * @expectedException Ems\Contracts\Core\Errors\Unsupported
     **/
    public function test_render_without_constraint_parameters_throws_exception()
    {

        $bindings = [];

        $c = $this->where('n', new Constraint('equals', [], '='));

        $d = $this->newDialect();

        $sql = $d->render($c, $bindings);

    }

    /**
     * @expectedException Ems\Contracts\Core\Errors\Unsupported
     **/
    public function test_render_with_unsupported_operator_throws_exception()
    {

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

        $this->assertEquals('`updated_at` IS NULL', $sql);
        $this->assertEquals([], $bindings);

        $bindings = [];

        $c = $this->where('updated_at', null);

        $d = $this->newDialect();

        $sql = $d->render($c, $bindings);

        $this->assertEquals('`updated_at` IS NULL', $sql);
        $this->assertEquals([], $bindings);

    }

    public function test_render_in_operator()
    {

        $bindings = [];

        $c = $this->where('updated_at', 'in', [1,2,3]);

        $d = $this->newDialect();

        $sql = $d->render($c, $bindings);

        $this->assertEquals('`updated_at` IN (?,?,?)', $sql);
        $this->assertEquals([1,2,3], $bindings);

        $bindings = [];

        $c = $this->where('updated_at', [3,4,5,6]);

        $d = $this->newDialect();

        $sql = $d->render($c, $bindings);

        $this->assertEquals('`updated_at` IN (?,?,?,?)', $sql);
        $this->assertEquals([3,4,5,6], $bindings);

    }

    public function test_render_condition_with_expression_in_constraint()
    {

        $bindings = [];

        $c = $this->where('addresses.country_code', 'like', SQL::key('users.locale'));

        $d = $this->newDialect();

        $sql = $d->render($c, $bindings);

        $this->assertEquals('`addresses`.`country_code` LIKE `users`.`locale`', $sql);
        $this->assertEquals([], $bindings);

    }

    /**
     * @expectedException Ems\Contracts\Core\Errors\Unsupported
     **/
    public function test_render_with_unsupported_parameter_in_constraint_throws_exception()
    {

        $bindings = [];

        $c = $this->where('a', 'like', new \stdclass);
        $d = $this->newDialect();

        $sql = $d->render($c, $bindings);

    }

    protected function newDialect()
    {
        return new MySQLDialect();
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

class MySQLDialectTest_Expression extends Expression
{
}
