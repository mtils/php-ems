<?php
/**
 *  * Created by mtils on 15.02.20 at 06:44.
 **/

namespace Ems\Contracts\Model\Database;

use Ems\Contracts\Model\Database\Query;
use Ems\Core\Expression;
use Ems\TestCase;
use PHPUnit\Framework\Attributes\Test;

use function str_replace;

class QueryTest extends TestCase
{
    #[Test] public function it_instantiates()
    {
        $this->assertInstanceOf(Query::class, $this->newQuery());
    }

    #[Test] public function setting_and_getting_table()
    {
        $table = 'users';
        $query = $this->newQuery()->from($table);
        $this->assertEquals($table, $query->table);

        $newTable = 'projects';
        $query->table = $newTable;
        $this->assertEquals($newTable, $query->table);
    }

    #[Test] public function select_adds_columns()
    {
        $query = $this->newQuery();
        $this->assertEquals([], $query->columns);
        $query->select('one');
        $this->assertEquals(['one'], $query->columns);
        $query->select('two', 'three');
        $this->assertEquals(['one', 'two', 'three'], $query->columns);
        $query->select('four', 'five');
        $this->assertEquals(['one', 'two', 'three','four','five'], $query->columns);
    }

    #[Test] public function select_adds_array_of_columns()
    {
        $query = $this->newQuery();
        $this->assertEquals([], $query->columns);
        $query->select(['one']);
        $this->assertEquals(['one'], $query->columns);
        $query->select(['two', 'three']);
        $this->assertEquals(['one', 'two', 'three'], $query->columns);
        $query->select(['four', 'five']);
        $this->assertEquals(['one', 'two', 'three','four','five'], $query->columns);
    }

    #[Test] public function reset_columns()
    {
        $query = $this->newQuery();
        $this->assertEquals([], $query->columns);
        $query->select('one');
        $this->assertEquals(['one'], $query->columns);
        $query->select('two', 'three');
        $this->assertEquals(['one', 'two', 'three'], $query->columns);
        $query->select();
        $this->assertEquals([], $query->columns);
    }

    #[Test] public function set_columns_resets_columns()
    {
        $query = $this->newQuery();
        $this->assertEquals([], $query->columns);
        $query->select(['one']);
        $this->assertEquals(['one'], $query->columns);
        $query->select(['two', 'three']);
        $this->assertEquals(['one', 'two', 'three'], $query->columns);
        $query->columns = ['four', 'five'];
        $this->assertEquals(['four','five'], $query->columns);
    }

    #[Test] public function distinct_makes_query_distinct()
    {
        $query = $this->newQuery();
        $this->assertFalse($query->distinct);
        $query->distinct(true);
        $this->assertTrue($query->distinct);
        $query->distinct(false);
        $this->assertFalse($query->distinct);
        $query->distinct = true;
        $this->assertTrue($query->distinct);
        $query->distinct = false;
        $this->assertFalse($query->distinct);
    }

    #[Test] public function join_creates_join()
    {
        $query = $this->newQuery();
        $query->from('users')
              ->join('addresses')
              ->as('delivery_address')
              ->on('users.id', '=', 'delivery_address.user_id');

        $this->assertEquals('addresses', $query->joins[0]->table);
        $this->assertEquals('delivery_address', $query->joins[0]->alias);
        $this->assertEquals('users.id', $query->joins[0]->conditions->first()->left);
        $this->assertEquals('=', $query->joins[0]->conditions->first()->operator);
        $this->assertEquals('delivery_address.user_id', $query->joins[0]->conditions->first()->right);
        $this->assertTrue($query->joins[0]->conditions->first()->rightIsKey);

    }

    #[Test] public function where_adds_predicate()
    {
        $query = $this->newQuery();
        $this->assertSame($query, $query->where('foo', 'bar'));
        $this->assertEquals('foo', $query->conditions->first()->left);
        $this->assertEquals('=', $query->conditions->first()->operator);
        $this->assertEquals('bar', $query->conditions->first()->right);
    }

    #[Test] public function where_adds_predicates()
    {
        $query = $this->newQuery();

        $this->assertSame($query, $query->where('foo', 'bar'));
        $this->assertEquals('foo', $query->conditions->first()->left);
        $this->assertEquals('=', $query->conditions->first()->operator);
        $this->assertEquals('bar', $query->conditions->first()->right);

        $this->assertSame($query, $query->where('quantity', '>', 30));
        $this->assertEquals('quantity', $query->conditions->expressions[1]->left);
        $this->assertEquals('>', $query->conditions->expressions[1]->operator);
        $this->assertEquals(30, $query->conditions->expressions[1]->right);
    }

    #[Test] public function invoke_adds_parentheses()
    {
        $query = $this->newQuery();
        $query('OR')->where('age', '<', 30)
                             ->where('age', '>', 60);

        $this->assertEquals('OR', $query->conditions->first()->boolean);
        $this->assertEquals('<', $query->conditions->first()->expressions[0]->operator);
        $this->assertEquals('>', $query->conditions->first()->expressions[1]->operator);

    }

    #[Test] public function groupBy_adds_groupBy()
    {
        $query = $this->newQuery();
        $query->groupBy('birthday');
        $this->assertEquals(['birthday'], $query->groupBys);

        $query->groupBy('department')->groupBy('cost_centre');

        $this->assertEquals(['birthday', 'department', 'cost_centre'], $query->groupBys);
    }

    #[Test] public function groupBy_add_many_groupBy()
    {
        $query = $this->newQuery();
        $query->groupBy('birthday', 'department');
        $this->assertEquals(['birthday', 'department'], $query->groupBys);

        $query->groupBys = [];
        $this->assertEquals([], $query->groupBys);

        $query->groupBy(['birthday', 'department']);
        $this->assertEquals(['birthday', 'department'], $query->groupBys);

    }

    #[Test] public function orderBy_adds_orderBy()
    {
        $query = $this->newQuery();
        $query->orderBy('id');

        $this->assertEquals(['id' => 'ASC'], $query->orderBys);

        $query->orderBy('booking_date', 'DESC');

        $this->assertEquals([
            'id'            => 'ASC',
            'booking_date'  => 'DESC'
        ], $query->orderBys);
    }

    #[Test] public function orderBy_adds_orderBy_Expression()
    {
        $query = $this->newQuery();
        $sort = new Expression('SUM(amount) AS amount_sum');
        $query->orderBy($sort);

        $this->assertEquals(['expression-0' => $sort], $query->orderBys);

    }

    #[Test] public function reset_orderBy()
    {
        $query = $this->newQuery();
        $query->orderBy('id');

        $this->assertEquals(['id' => 'ASC'], $query->orderBys);

        $query->orderBys = [];

        $this->assertEquals([], $query->orderBys);

    }

    #[Test] public function having_adds_predicate()
    {
        $query = $this->newQuery();
        $this->assertSame($query, $query->having('foo', 'bar'));
        $this->assertEquals('foo', $query->havings->first()->left);
        $this->assertEquals('=', $query->havings->first()->operator);
        $this->assertEquals('bar', $query->havings->first()->right);
    }

    #[Test] public function get_and_set_offset()
    {
        $query = $this->newQuery();
        $this->assertNull($query->offset);
        $this->assertNull($query->limit);
        $this->assertSame($query, $query->offset(12));
        $this->assertEquals(12, $query->offset);

        $this->assertSame($query, $query->offset(15, 24));
        $this->assertEquals(15, $query->offset);
        $this->assertEquals(24, $query->limit);

        $query->offset = 50;
        $this->assertEquals(50, $query->offset);

    }

    #[Test] public function get_and_set_limit()
    {
        $query = $this->newQuery();
        $this->assertNull($query->offset);
        $this->assertNull($query->limit);
        $this->assertSame($query, $query->limit(12));
        $this->assertEquals(12, $query->limit);

        $this->assertSame($query, $query->limit(24, 15));
        $this->assertEquals(15, $query->offset);
        $this->assertEquals(24, $query->limit);

        $query->limit = 50;
        $this->assertEquals(50, $query->limit);

    }

    #[Test] public function get_and_set_values()
    {
        $query = $this->newQuery();
        $query->values('first_name', 'Aaron');
        $this->assertEquals(['first_name' => 'Aaron'], $query->values);

        $query->values('last_name', 'Becker');

        $this->assertEquals([
            'first_name' => 'Aaron',
            'last_name'  => 'Becker'
        ], $query->values);

        $query->values = ['first_name' => 'Aaron'];
        $this->assertEquals(['first_name' => 'Aaron'], $query->values);

    }

    #[Test] public function operation_is_select_without_values()
    {
        $query = $this->newQuery();
        $this->assertEquals('SELECT', $query->operation);
    }

    #[Test] public function operation_is_insert_with_values()
    {
        $query = $this->newQuery();
        $query->values('first_name', 'Olaf');
        $this->assertEquals('INSERT', $query->operation);
    }

    #[Test] public function operation_is_update_with_values_and_conditions()
    {
        $query = $this->newQuery();
        $query->values('first_name', 'Olaf')->where('id', 88);
        $this->assertEquals('UPDATE', $query->operation);
    }

    #[Test] public function operation_is_what_setted_by_property()
    {
        $query = $this->newQuery();
        $query->operation = 'DELETE';
        $this->assertEquals('DELETE', $query->operation);
    }

    #[Test] public function get_unknown_property()
    {
        $query = $this->newQuery();
        $this->assertNull($query->__get('foo'));
    }

    #[Test] public function attach_and_detach_queries()
    {
        $query = $this->newQuery();
        $countQuery = $this->newQuery();

        $this->assertNull($query->getAttached('count'));
        $this->assertSame($query, $query->attach('count', $countQuery));
        $this->assertSame($countQuery, $query->getAttached('count'));

        $this->assertEquals(['count'=>$countQuery], $query->attachments);

        $relatedQuery = $this->newQuery();
        $this->assertSame($query, $query->attach('related', $relatedQuery));
        $this->assertSame($relatedQuery, $query->getAttached('related'));
        $this->assertEquals([
            'count'     =>  $countQuery,
            'related'   =>  $relatedQuery
                            ], $query->attachments);

        $this->assertSame($query, $query->detach('count'));
        $this->assertEquals([
                                'related'   =>  $relatedQuery
                            ], $query->attachments);
    }

    protected function newQuery()
    {
        return new Query();
    }

}