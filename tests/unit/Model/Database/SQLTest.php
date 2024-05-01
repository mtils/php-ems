<?php


namespace Ems\Model\Database;

use Ems\Core\Expression;
use Ems\Core\KeyExpression;
use Ems\Expression\ConditionGroup;
use Ems\Expression\Constraint;
use Ems\Model\Database\Dialects\AbstractDialect;
use Ems\Model\Database\Dialects\MySQLDialect;

class SQLTest extends \Ems\TestCase
{

    public function test_render_renders_sql_statement_without_bindings()
    {
        $query = 'SELECT * FROM users WHERE id=45';
        $this->assertEquals($query, SQL::render($query));
    }

    public function test_render_renders_sql_statement_with_sequential_bindings()
    {
        $query = 'SELECT * FROM users WHERE id = ? and age > ?';
        $rendered = 'SELECT * FROM users WHERE id = 5 and age > 15';
        $this->assertEquals($rendered, SQL::render($query, [5,15]));
    }

    public function test_render_renders_sql_statement_with_named_bindings()
    {
        $query = 'SELECT * FROM users WHERE id = :id and age > :age';
        $rendered = 'SELECT * FROM users WHERE id = 5 and age > 15';
        $this->assertEquals($rendered, SQL::render($query, ['id'=>5,'age'=>15]));
    }

    public function test_key_returns_KeyExpression()
    {
        $e = SQL::key('foo');
        $this->assertInstanceof(KeyExpression::class, $e);
        $this->assertEquals('foo', $e);
    }

    public function test_rule_returns_Constraint()
    {
        $e = SQL::rule('foo', ['a']);
        $this->assertInstanceof(Constraint::class, $e);
        $this->assertEquals('foo', $e->name());
        $this->assertEquals('foo', $e->operator());
        $this->assertEquals(['a'], $e->parameters());
    }

    public function test_where_creates_ConditionGroup()
    {
        $e = SQL::where(function ($e) {
            return $e->where('a', 'b');
        });

        $this->assertInstanceof(ConditionGroup::class, $e);
        $this->assertEquals('and', strtolower($e->operator()));

        $e = SQL::where('a', 'b');

        $this->assertInstanceof(ConditionGroup::class, $e);
        $this->assertEquals('and', strtolower($e->operator()));
        $this->assertEquals('a', (string)$e->expressions()[0]->operand());
        $this->assertEquals('=', $e->expressions()[0]->constraint()->operator());
        $this->assertEquals('b', $e->expressions()[0]->constraint()->parameters()[0]);

        $e = SQL::where('a', '<>', 'b');

        $this->assertInstanceof(ConditionGroup::class, $e);
        $this->assertEquals('and', strtolower($e->operator()));
        $this->assertEquals('a', (string)$e->expressions()[0]->operand());
        $this->assertEquals('<>', $e->expressions()[0]->constraint()->operator());
        $this->assertEquals('b', $e->expressions()[0]->constraint()->parameters()[0]);
    }

    public function test_raw_returns_Expression()
    {
        $e = SQL::raw('foo');
        $this->assertInstanceof(Expression::class, $e);
        $this->assertEquals('foo', $e);
    }

    public function test_dialect_uses_custom_extension()
    {
        $test = $this->mock(AbstractDialect::class);

        SQL::extend('bavarian', function () use ($test) {
            return $test;
        });

        $this->assertSame($test, SQL::dialect('bavarian'));
    }

    public function test_dialect_uses_mysql_dialect()
    {
        $this->assertInstanceOf(MySQLDialect::class, SQL::dialect('mysql'));
    }

    public function test_dialect_throws_Exception_if_not_supported()
    {
        $this->expectException(
            \Ems\Core\Exceptions\NotImplementedException::class
        );
        SQL::dialect('informix');
    }
}
