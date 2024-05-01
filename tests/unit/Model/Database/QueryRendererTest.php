<?php

/**
 *  * Created by mtils on 23.02.20 at 07:14.
 **/
// phpcs:disable Generic.Commenting.Todo.Found

namespace Ems\Model\Database;

use DateTime;
use Ems\Contracts\Core\Renderable;
use Ems\Contracts\Core\Renderer;
use Ems\Contracts\Model\Database\Dialect;
use Ems\Contracts\Model\Database\JoinClause;
use Ems\Contracts\Model\Database\Parentheses;
use Ems\Contracts\Model\Database\Predicate;
use Ems\Contracts\Model\Database\SQLExpression;
use Ems\Core\Expression;
use Ems\Core\KeyExpression;
use Ems\Model\Database\Dialects\AbstractDialect;
use Ems\Model\Database\Dialects\SQLiteDialect;
use Ems\TestCase;

use Ems\Testing\Cheat;
use PHPUnit\Framework\Attributes\Test;
use stdClass;

use function array_values;
use function str_replace;

class QueryRendererTest extends TestCase
{
    #[Test] public function it_implements_interface()
    {
        $this->assertInstanceOf(Renderer::class, $this->newRenderer());
    }

    #[Test] public function canRender_is_only_true_on_queries()
    {
        $renderer = $this->newRenderer();
        $query = $this->newQuery();

        $this->assertTrue($renderer->canRender($query));

        $renderable = $this->mock(Renderable::class);

        $this->assertFalse($renderer->canRender($renderable));

    }

    #[Test] public function render_throws_exception_if_no_query()
    {
        $this->expectException(
            \Ems\Core\Exceptions\UnsupportedParameterException::class
        );
        $renderer = $this->newRenderer();
        $renderable = $this->mock(Renderable::class);
        $renderer->render($renderable);
    }

    #[Test] public function render_simple_select()
    {
        $renderer = $this->newRenderer();
        $query = $this->newQuery()->from('users');
        $this->assertSql($renderer->render($query), 'SELECT * FROM "users"');
        $this->assertInstanceOf(SQLExpression::class, $renderer->render($query));
    }

    #[Test] public function test_render_throws_exception_if_operation_unknown()
    {
        $this->expectException(
            \Ems\Core\Exceptions\UnsupportedParameterException::class
        );
        $query = $this->newQuery();
        $query->operation = 'FOO';
        $this->newRenderer()->render($query);
    }

    #[Test] public function render_simple_distinct_select()
    {
        $renderer = $this->newRenderer();
        $query = $this->newQuery()->from('users')->distinct();
        $this->assertSql($renderer->render($query), 'SELECT DISTINCT * FROM "users"');
        $this->assertInstanceOf(SQLExpression::class, $renderer->render($query));
    }

    #[Test] public function renderColumns_renders_strings()
    {
        $renderer = $this->newRenderer();
        $columns = [
            '*'
        ];
        $this->assertEquals('*', $renderer->renderColumns($columns));

        $columns = [
            'id', 'login', 'password'
        ];

        $this->assertEquals('"id", "login", "password"', $renderer->renderColumns($columns));
    }

    #[Test] public function renderColumns_renders_expressions()
    {
        $renderer = $this->newRenderer();

        $countString = 'COUNT(*)';
        $subSelectString = '(SELECT "recipient" FROM "address" WHERE postcode = ?) AS address_line1';

        $count = new Expression($countString);
        $subSelect = new SQLExpression($subSelectString, [76399]);

        $columns = [
            'id', 'login', $count, 'password', $subSelect
        ];

        $bindings = [];

        $this->assertEquals('"id", "login", ' . $countString . ', "password"' . ', ' . $subSelectString, $renderer->renderColumns($columns, $bindings));

        $this->assertEquals($subSelect->getBindings(), $bindings);
    }

    #[Test] public function renderColumns_renders_aliases()
    {
        $renderer = $this->newRenderer();
        $columns = [
            '*'
        ];
        $this->assertEquals('*', $renderer->renderColumns($columns));

        $columns = [
            'id', 'login', new KeyExpression('password', 'pass')
        ];

        $this->assertEquals('"id", "login", "password" AS \'pass\'', $renderer->renderColumns($columns));
    }

    #[Test] public function renderColumns_renders_expression_without_dialect()
    {
        $renderer = $this->newRenderer(false);

        $countString = 'COUNT(*)';
        $subSelectString = '(SELECT "recipient" FROM "address" WHERE postcode = ?) AS address_line1';

        $count = new Expression($countString);
        $subSelect = new SQLExpression($subSelectString, [76399]);

        $columns = [
            'id', 'login', $count, 'password', $subSelect
        ];

        $bindings = [];

        $this->assertEquals('id, login, ' . $countString . ', password' . ', ' . $subSelectString, $renderer->renderColumns($columns, $bindings));

        $this->assertEquals($subSelect->getBindings(), $bindings);
    }

    #[Test] public function get_and_set_Dialect()
    {
        /* @var Dialect $dialect */
        $dialect = $this->mock(Dialect::class);

        $renderer = $this->newRenderer();
        $this->assertInstanceOf(AbstractDialect::class, $renderer->getDialect());
        $this->assertSame($renderer, $renderer->setDialect($dialect));
        $this->assertSame($dialect, $renderer->getDialect());
    }

    #[Test] public function renderColumns_renders_strings_without_dialect()
    {
        $renderer = $this->newRenderer(false);
        $columns = [
            'id', 'login', 'password'
        ];

        $this->assertEquals('id, login, password', $renderer->renderColumns($columns));
    }

    #[Test] public function renderColumns_renders_raw_string()
    {
        $renderer = $this->newRenderer(false);
        $columns = ['id, login, password'];

        $this->assertEquals($columns[0], $renderer->renderColumns($columns));

    }

    #[Test] public function renderJoins_renders_join()
    {
        $renderer = $this->newRenderer();

        $join = new JoinClause('addresses');
        $join->on('users.address_id', '=', 'addresses.id');

        $string = $renderer->renderJoins([$join]);
        $this->assertSql('JOIN "addresses" ON "users"."address_id" = "addresses"."id"', $string);

    }

    #[Test] public function renderJoins_renders_joins()
    {
        $renderer = $this->newRenderer();

        $join = new JoinClause('addresses');
        $join->on('users.address_id', '=', 'addresses.id')->left();

        $join2 = (new JoinClause('address_types'))->as('address_type');
        $join2->on('addresses.type_id', 'address_type.id')
              ->inner();

        $string = $renderer->renderJoins([$join, $join2]);
        $query  = 'LEFT JOIN "addresses" ON "users"."address_id" = "addresses"."id"' . "\n";
        $query .= 'INNER JOIN "address_types" AS "address_type" ON "addresses"."type_id" = "address_type"."id"';

        $this->assertSql($query, $string);

    }

    #[Test] public function renderJoins_renders_join_with_additional_condition()
    {
        $renderer = $this->newRenderer();

        $join = (new JoinClause('addresses'))->right();
        $join->on('users.address_id', '=', 'addresses.id');
        $join('AND', function (Parentheses $group) {
            $group->where('addresses.deleted_at', null);
        });

        /* $join('AND', function (Parentheses $group) {
            $group->where('users.address_id', '=', 'addresses.id')
                ->where('addresses.deleted_at', null);
        });*/

        $string = $renderer->renderJoins([$join]);
        $query  = 'RIGHT JOIN "addresses" ON "users"."address_id" = "addresses"."id"' . "\n";
        $query .= 'AND "addresses"."deleted_at" IS NULL';

        $this->assertSql($query, $string);

    }

    #[Test] public function renderJoins_renders_join_with_multiple_conditions_without_on()
    {
        $renderer = $this->newRenderer();

        $join = (new JoinClause('addresses'))->right();

        $join('AND', function (Parentheses $group) {
            $group->where('users.address_id', '=', 'addresses.id')
                  ->where('addresses.deleted_at', '<>', null);
        });

        $string = $renderer->renderJoins([$join]);
        $query  = 'RIGHT JOIN "addresses" ON "users"."address_id" = "addresses"."id"' . "\n";
        $query .= 'AND "addresses"."deleted_at" IS NOT NULL';

        $this->assertSql($query, $string);

    }

    #[Test] public function renderJoins_renders_join_without_conditions()
    {
        $renderer = $this->newRenderer();

        $join = (new JoinClause('addresses'))->right();

        $string = $renderer->renderJoins([$join]);
        $query  = 'RIGHT JOIN "addresses"';

        $this->assertSql($query, $string);

    }

    #[Test] public function renderGroupBy_renders_simple_column()
    {
        $groupBys = ['addresses.id'];
        $renderer = $this->newRenderer();

        $expression = $renderer->renderGroupBy($groupBys);
        $this->assertInstanceOf(SQLExpression::class, $expression);
        $this->assertSql('"addresses"."id"', "$expression");
    }

    #[Test] public function renderGroupBy_renders_multiple_columns()
    {
        $groupBys = ['group.id', 'addresses.id', 'postcode'];
        $renderer = $this->newRenderer();

        $expression = $renderer->renderGroupBy($groupBys);
        $this->assertInstanceOf(SQLExpression::class, $expression);
        $this->assertSql('"group"."id","addresses"."id","postcode"', "$expression");
    }

    #[Test] public function renderGroupBy_renders_columns_and_raw()
    {
        $groupBys = [new Expression('SUM(points)'), 'group.id'];
        $renderer = $this->newRenderer();

        $expression = $renderer->renderGroupBy($groupBys);
        $this->assertInstanceOf(SQLExpression::class, $expression);
        $this->assertSql('SUM(points),"group"."id"', "$expression");
    }

    #[Test] public function renderGroupBy_without_columns()
    {
        $groupBys = [];
        $renderer = $this->newRenderer();

        $expression = $renderer->renderGroupBy($groupBys);
        $this->assertInstanceOf(SQLExpression::class, $expression);
        $this->assertSame('', "$expression");
    }

    #[Test] public function renderOrderBy_without_columns()
    {
        $groupBys = [];
        $renderer = $this->newRenderer();

        $expression = $renderer->renderOrderBy($groupBys);
        $this->assertInstanceOf(SQLExpression::class, $expression);
        $this->assertSame('', "$expression");
    }

    #[Test] public function renderOrderBy_renders_simple_column()
    {
        $groupBys = ['addresses.id' => 'DESC'];
        $renderer = $this->newRenderer();

        $expression = $renderer->renderOrderBy($groupBys);
        $this->assertInstanceOf(SQLExpression::class, $expression);
        $this->assertSql('"addresses"."id" DESC', "$expression");
    }

    #[Test] public function renderOrderBy_renders_multiple_columns()
    {
        $groupBys = ['addresses.id' => 'DESC', 'id' => 'ASC'];
        $renderer = $this->newRenderer();

        $expression = $renderer->renderOrderBy($groupBys);
        $this->assertInstanceOf(SQLExpression::class, $expression);
        $this->assertSql('"addresses"."id" DESC,"id" ASC', "$expression");
    }

    #[Test] public function renderOrderBy_renders_columns_and_raw()
    {
        $groupBys = ['addresses.id' => 'DESC', new SQLExpression('COUNT(*)'), 'id' => 'ASC'];
        $renderer = $this->newRenderer();

        $expression = $renderer->renderOrderBy($groupBys);
        $this->assertInstanceOf(SQLExpression::class, $expression);
        $this->assertSql('"addresses"."id" DESC,COUNT(*),"id" ASC', "$expression");
    }

    #[Test] public function renderConditions_renders_Predicate()
    {
        $renderer = $this->newRenderer();
        $predicate = new Predicate('id', '>', 18);

        $exp = $renderer->renderConditions([$predicate]);

        $this->assertSql('"id" > ?', $exp->toString());
        $this->assertSame([18], $exp->getBindings());

    }

    #[Test] public function renderConditions_renders_Predicates()
    {
        $renderer = $this->newRenderer();
        $group = new Parentheses('AND');

        $group->where(new Predicate('id', '>', 18));
        $group->where('last_name', 'LIKE', '%eyer');

        $exp = $renderer->renderConditions($group);

        $this->assertSql('"id" > ? AND "last_name" LIKE ?', $exp->toString());
        $this->assertSame([18, '%eyer'], $exp->getBindings());

    }

    #[Test] public function renderConditions_renders_Expression()
    {
        $renderer = $this->newRenderer();
        $group = new Parentheses('AND');

        $group->where(new Predicate('id', '>', 18));
        $group->where(new Expression('COUNT(DISTINCT id) > 45'));

        $exp = $renderer->renderConditions($group);

        $this->assertSql('"id" > ? AND COUNT(DISTINCT id) > 45', $exp->toString());
        $this->assertSame([18], $exp->getBindings());

    }

    #[Test] public function renderConditions_renders_raw_strings()
    {
        $renderer = $this->newRenderer();
        $group = new Parentheses('AND');

        $group->where(new Predicate('id', '>', 18));
        $group->where('COUNT(DISTINCT id) > 45');

        $exp = $renderer->renderConditions($group);

        $this->assertSql('"id" > ? AND COUNT(DISTINCT id) > 45', $exp->toString());
        $this->assertSame([18], $exp->getBindings());

    }

    #[Test] public function renderConditions_throws_exception_on_unsupported_condition()
    {
        $this->expectException(
            \Ems\Core\Exceptions\UnsupportedParameterException::class
        );
        $this->newRenderer()->renderConditions([new stdClass()]);
    }

    #[Test] public function renderConditions_renders_WHERE_IN()
    {
        $renderer = $this->newRenderer();
        $group = new Parentheses('AND');

        $group->where('id', 'in', [18]);

        $exp = $renderer->renderConditions($group);

        $this->assertSql('"id" in (?)', $exp->toString());
        $this->assertSame([18], $exp->getBindings());

    }

    #[Test] public function renderConditions_renders_WHERE_NOT_IN()
    {
        $renderer = $this->newRenderer();
        $group = new Parentheses('AND');
        $values = [18,19,20,21];

        $group->where('id', 'not in', $values);

        $exp = $renderer->renderConditions($group);

        $this->assertSql('"id" not in (?,?,?,?)', $exp->toString());
        $this->assertSame($values, $exp->getBindings());

    }

    #[Test] public function renderConditions_renders_Predicate_with_Expression()
    {
        $renderer = $this->newRenderer();
        $group = new Parentheses('AND');
        $group->where(new Predicate(new Expression('"name" IS NULL')));

        $exp = $renderer->renderConditions($group);

        $this->assertSql('"name" IS NULL', $exp->toString());

    }

    #[Test] public function renderConditions_renders_unknown_null_operator()
    {
        $renderer = $this->newRenderer();
        $group = new Parentheses('AND');

        $group->where('id', '>', null);

        $exp = $renderer->renderConditions($group);

        $this->assertSql('"id" > ?', $exp->toString());
        $this->assertSame([null], $exp->getBindings());

    }

    #[Test] public function renderPredicatePart_with_unknown_mode_throws_exception()
    {
        $this->expectException(
            \Ems\Core\Exceptions\UnsupportedParameterException::class
        );
        $renderer = $this->newRenderer();
        $bindings = [];
        Cheat::call($renderer, 'renderPredicatePart', ['foo', &$bindings, 'bar']);
    }

    #[Test] public function renderSelect_with_condition()
    {
        $query = $this->newQuery()->from('users')
                                  ->where('id', '>', 333);

        $exp = $this->newRenderer()->renderSelect($query);

        $this->assertSql('SELECT * FROM "users" WHERE "id" > ?', $exp->toString());
        $this->assertSame([333], $exp->getBindings());
    }

    #[Test] public function renderSelect_with_conditions()
    {
        $query = $this->newQuery()
            ->select('id', 'login', 'age')
            ->from('users')
            ->where('id', '>', 333)
            ->where('login', 'LIKE', '%@gmail.com');

        $exp = $this->newRenderer()->renderSelect($query);

        $this->assertSql('SELECT "id", "login", "age" FROM "users" WHERE "id" > ? AND "login" LIKE ?', $exp->toString());
        $this->assertSame([333, '%@gmail.com'], $exp->getBindings());
    }

    #[Test] public function renderSelect_with_join()
    {
        $query = $this->newQuery()->from('users');

        $query->join('addresses')
                ->as('address')
                ->on('users.address_id', 'address.id');

        $query->where('users.id', '>', 333);

        $exp = $this->newRenderer()->renderSelect($query);

        $sql = 'SELECT * FROM "users"';
        $sql .= ' JOIN "addresses" AS "address" ON "users"."address_id" = "address"."id"';
        $sql .= ' WHERE "users"."id" > ?';
        $this->assertSql($sql, $exp->toString());
        $this->assertSame([333], $exp->getBindings());
    }

    #[Test] public function renderSelect_with_joins()
    {
        $query = $this->newQuery()->from('users');

        $from = new DateTime('2018-06-01 00:00:00');
        $to = new DateTime('2019-06-01 00:00:00');

        $query->join('addresses')->right()
            ->as('address')
            ->on('users.address_id', 'address.id')
            ->join('countries')->inner()
            ->as('country')
            ->on('country.id', 'address.country_id')
            ->join('delivery_services')->left()
            ->as('delivery_service')('AND', function (Parentheses $group) use ($from, $to) {
                $group->where('delivery_service.user_id', 'users.id')
                    ->where('delivery_service.valid_from', '>=', $from)
                    ->where('delivery_service.valid_until', '<=', $to);
            });

        $query->where('users.id', '>', 333);

        $exp = $this->newRenderer()->renderSelect($query);

        $sql = 'SELECT * FROM "users"';
        $sql .= ' RIGHT JOIN "addresses" AS "address" ON "users"."address_id" = "address"."id"';
        $sql .= ' INNER JOIN "countries" AS "country" ON "country"."id" = "address"."country_id"';
        $sql .= ' LEFT JOIN "delivery_services" AS "delivery_service" ON "delivery_service"."user_id" = "users"."id"';
        $sql .= ' AND "delivery_service"."valid_from" >= ?';
        $sql .= ' AND "delivery_service"."valid_until" <= ?';
        $sql .= ' WHERE "users"."id" > ?';
        $this->assertSql($sql, $exp->toString());
        $this->assertSame([$from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s'), 333], $exp->getBindings());
    }

    #[Test] public function render_groupBy()
    {
        $query = $this->newQuery()->from('users')
            ->where('id', '>', 333)
        ->groupBy('category_id');

        $exp = $this->newRenderer()->renderSelect($query);

        $this->assertSql('SELECT * FROM "users" WHERE "id" > ? GROUP BY "category_id"', $exp->toString());
        $this->assertSame([333], $exp->getBindings());
    }

    #[Test] public function render_groupBy_having()
    {
        $query = $this->newQuery()
            ->select('id', new Expression('MAX(id) AS max_id'))
            ->from('users')
            ->where('id', '>', 333)
            ->groupBy('category_id', 'source')
            ->having('max_id', '>', 55);

        $exp = $this->newRenderer()->renderSelect($query);

        $sql = 'SELECT "id", MAX(id) AS max_id FROM "users"';
        $sql .= ' WHERE "id" > ? GROUP BY "category_id","source"';
        $sql .= ' HAVING "max_id" > ?';

        $this->assertSql($sql, $exp->toString());
        $this->assertSame([333, 55], $exp->getBindings());
    }

    #[Test] public function render_orderBy()
    {
        $query = $this->newQuery()->from('users')
            ->where('id', '>', 333)
            ->orderBy('category_id');

        $exp = $this->newRenderer()->renderSelect($query);

        $this->assertSql('SELECT * FROM "users" WHERE "id" > ? ORDER BY "category_id" ASC', $exp->toString());
        $this->assertSame([333], $exp->getBindings());
    }

    #[Test] public function render_insert()
    {
        $query = $this->newQuery()->from('users');
        $values = [
            'first_name'    => 'John',
            'last_name'     => 'Doe',
            'age'           => 45
        ];

        $exp = $this->newRenderer()->renderInsert($query, $values);

        $sql = 'INSERT INTO "users" ("first_name", "last_name", "age")';
        $sql .= ' VALUES (?, ?, ?)';
        $this->assertSql($sql, $exp->toString());
        $this->assertEquals(array_values($values), $exp->getBindings());
    }

    #[Test] public function render_insert_with_raw_expression()
    {
        $query = $this->newQuery()->from('users');
        $raw = '(SELECT last_name FROM users LIMIT 1)';
        $values = [
            'first_name'    => 'John',
            'last_name'     => new Expression($raw),
            'age'           => 45
        ];

        $exp = $this->newRenderer()->renderInsert($query, $values);

        $sql = 'INSERT INTO "users" ("first_name", "last_name", "age")';
        $sql .= " VALUES (?, $raw, ?)";
        $this->assertSql($sql, $exp->toString());
        $this->assertEquals(['John', 45], $exp->getBindings());
    }

    #[Test] public function render_insert_with_sql_expression()
    {
        $query = $this->newQuery()->from('users');
        $raw = '(SELECT last_name FROM users WHERE first_name LIKE ? LIMIT 1)';
        $values = [
            'first_name'    => 'John',
            'last_name'     => new SQLExpression($raw, ['Sophie']),
            'age'           => 45
        ];

        $exp = $this->newRenderer()->renderInsert($query, $values);

        $sql = 'INSERT INTO "users" ("first_name", "last_name", "age")';
        $sql .= " VALUES (?, $raw, ?)";
        $this->assertSql($sql, $exp->toString());
        $this->assertEquals(['John', "Sophie", 45], $exp->getBindings());
    }

    #[Test] public function render_update()
    {
        $query = $this->newQuery()->from('users');
        $values = [
            'first_name'    => 'John',
            'last_name'     => 'Doe',
            'age'           => 45
        ];

        $exp = $this->newRenderer()->renderUpdate($query, $values);

        $sql = 'UPDATE "users" SET "first_name" = ?, "last_name" = ?, "age" = ?';
        $this->assertSql($sql, $exp->toString());
        $this->assertEquals(array_values($values), $exp->getBindings());
    }

    #[Test] public function render_update_with_condition()
    {
        $query = $this->newQuery()->from('users');
        $query->where('id', 88);
        $values = [
            'first_name'    => 'John',
            'last_name'     => 'Doe',
            'age'           => 45
        ];

        $exp = $this->newRenderer()->renderUpdate($query, $values);

        $sql = 'UPDATE "users" SET "first_name" = ?, "last_name" = ?, "age" = ? WHERE "id" = ?';
        $this->assertSql($sql, $exp->toString());
        $bindings = array_values($values);
        $bindings[] = 88;
        $this->assertEquals($bindings, $exp->getBindings());
    }

    #[Test] public function render_update_with_SQLExpression()
    {
        $query = $this->newQuery()->from('users');
        $query->where('id', 88);

        $raw = '(SELECT first_name FROM templates WHERE last_name = ? LIMIT 1)';

        $values = [
            'first_name'    => new SQLExpression($raw, ['White']),
            'last_name'     => 'Doe',
            'age'           => 45
        ];

        $exp = $this->newRenderer()->renderUpdate($query, $values);

        $sql = 'UPDATE "users" SET "first_name" = ' . $raw . ', "last_name" = ?, "age" = ? WHERE "id" = ?';
        $this->assertSql($sql, $exp->toString());
        $bindings = ['White', 'Doe', 45, 88];
        $this->assertEquals($bindings, $exp->getBindings());
    }

    #[Test] public function render_update_with_Expression()
    {
        $query = $this->newQuery()->from('users');
        $query->where('id', 88);

        $raw = '(SELECT first_name FROM templates WHERE last_name = ? LIMIT 1)';

        $values = [
            'first_name'    => new Expression($raw),
            'last_name'     => 'Doe',
            'age'           => 45
        ];

        $exp = $this->newRenderer()->renderUpdate($query, $values);

        $sql = 'UPDATE "users" SET "first_name" = ' . $raw . ', "last_name" = ?, "age" = ? WHERE "id" = ?';
        $this->assertSql($sql, $exp->toString());
        $bindings = ['Doe', 45, 88];
        $this->assertEquals($bindings, $exp->getBindings());
    }

    #[Test] public function render_delete()
    {
        $query = $this->newQuery()->from('users');
        $exp = $this->newRenderer()->renderDelete($query);

        $sql = 'DELETE FROM "users"';

        $this->assertSql($sql, $exp->toString());
        $this->assertEquals([], $exp->getBindings());
    }

    #[Test] public function render_delete_with_condition()
    {
        $query = $this->newQuery()->from('users');
        $query->where('id', 88);

        $exp = $this->newRenderer()->renderDelete($query);

        $sql = 'DELETE FROM "users" WHERE "id" = ?';

        $this->assertSql($sql, $exp->toString());
        $this->assertEquals([88], $exp->getBindings());
    }

    protected function newRenderer($dialect=true)
    {
        $renderer = new QueryRenderer();

        if ($dialect instanceof Dialect) {
            $renderer->setDialect($dialect);
        }

        if ($dialect === true) {
            $renderer->setDialect($this->newDialect());
        }

        return $renderer;
    }

    protected function newQuery()
    {
        return new Query();
    }

    protected function newDialect()
    {
        return new SQLiteDialect();
    }

    protected function assertSql($expected, $actual, $message = '')
    {
        $expectedCmp = str_replace("\n", ' ', $expected);
        $actualCmp = str_replace("\n", ' ', $actual);
        $message = $message ?: "Expected SQL: '$expected' did not match '$actual";
        $this->assertEquals($expectedCmp, $actualCmp, $message);
    }
}