<?php
/**
 *  * Created by mtils on 22.02.20 at 11:17.
 **/

namespace Ems\Contracts\Model\Database;


use Ems\TestCase;

class JoinClauseTest extends TestCase
{
    /**
     * @test
     */
    public function it_instantiates()
    {
        $this->assertInstanceOf(JoinClause::class, $this->newClause());
    }

    /**
     * @test
     */
    public function on_adds_condition()
    {
        $clause = $this->newClause('addresses')
                       ->on('addresses.user_id', '=', 'users.id');

        $this->assertEquals('addresses', $clause->table);
        $predicate = $clause->conditions->expressions[0];
        $this->assertEquals('addresses.user_id', $predicate->left);
        $this->assertEquals('=', $predicate->operator);
        $this->assertEquals('users.id', $predicate->right);
        $this->assertTrue($predicate->rightIsKey);

    }

    /**
     * @test
     */
    public function on_adds_condition_with_two_args()
    {
        $clause = $this->newClause('addresses')
            ->on('addresses.user_id',  'users.id');

        $this->assertEquals('addresses', $clause->table);
        $predicate = $clause->conditions->expressions[0];
        $this->assertEquals('addresses.user_id', $predicate->left);
        $this->assertEquals('=', $predicate->operator);
        $this->assertEquals('users.id', $predicate->right);
        $this->assertTrue($predicate->rightIsKey);

    }

    /**
     * @test
     */
    public function on_adds_predicate_condition()
    {
        $predicate = new Predicate();
        $clause = $this->newClause('addresses')
            ->on($predicate);

        $this->assertEquals('addresses', $clause->table);
        $this->assertSame($predicate, $clause->conditions->expressions[0]);

    }

    /**
     * @test
     */
    public function as_sets_alias()
    {
        $clause = $this->newClause('addresses')->as('delivery_address')
            ->on('addresses.user_id', '=', 'users.id');

        $this->assertEquals('addresses', $clause->table);
        $predicate = $clause->conditions->expressions[0];
        $this->assertEquals('addresses.user_id', $predicate->left);
        $this->assertEquals('=', $predicate->operator);
        $this->assertEquals('users.id', $predicate->right);
        $this->assertEquals('delivery_address', $clause->alias);

    }

    /**
     * @test
     */
    public function get_and_set_join_direction()
    {
        $clause = $this->newClause('addresses')->as('delivery_address')
            ->on('addresses.user_id', '=', 'users.id');

        $this->assertEquals('', $clause->direction);
        $this->assertEquals('LEFT', $clause->left()->direction);
        $this->assertEquals('RIGHT', $clause->right()->direction);
        $this->assertEquals('FULL', $clause->full()->direction);
        $clause->direction = 'LEFT';
        $this->assertEquals('LEFT', $clause->direction);

    }

    /**
     * @test
     */
    public function get_and_set_unification()
    {
        $clause = $this->newClause('addresses')->as('delivery_address')
            ->on('addresses.user_id', '=', 'users.id');

        $this->assertEquals('', $clause->unification);
        $this->assertEquals('INNER', $clause->inner()->unification);
        $this->assertEquals('OUTER', $clause->outer()->unification);
        $this->assertEquals('CROSS', $clause->cross()->unification);
        $clause->unification = '';
        $this->assertEquals('', $clause->unification);

    }

    /**
     * @test
     */
    public function invoke_creates_inner_parentheses()
    {
        $clause = $this->newClause('addresses');

        $inner = $clause('OR', function (Parentheses $inner) {
            $inner->where('a', 'b');
        });

        $this->assertSame($inner, $clause->conditions->first());
        $this->assertSame('a', $clause->conditions->first()->expressions[0]->left);
        $this->assertSame('=', $clause->conditions->first()->expressions[0]->operator);
        $this->assertSame('b', $clause->conditions->first()->expressions[0]->right);
    }

    /**
     * @test
     */
    public function id_property_returns_alias_or_table()
    {
        $clause = $this->newClause('addresses')
            ->on('addresses.user_id', '=', 'users.id');

        $this->assertEquals($clause->table, $clause->id);
        $clause->alias = 'delivery_address';
        $this->assertEquals($clause->alias, $clause->id);
        $clause->table = 'user_addresses';
        $this->assertEquals($clause->alias, $clause->id);
        $clause->alias = '';
        $this->assertEquals($clause->table, $clause->id);
    }

    /**
     * @test
     */
    public function get_unknown_property_returns_null()
    {
        $this->assertNull($this->newClause()->__get('foo'));
    }

    /**
     * @test
     */
    public function set_unknown_property()
    {
        $this->assertNull($this->newClause()->__set('foo', 'bar'));
    }

    /**
     * @test
     */
    public function select_forwards_to_query()
    {
        $params = ['a', 'b'];
        $query = new Query();

        $clause = $this->newClause('users', $query);

        $this->assertSame($query, $clause->select(...$params));
        $this->assertEquals($params, $query->columns);
    }

    /**
     * @test
     */
    public function from_forwards_to_query()
    {
        $table = 'addresses';
        $query = new Query();

        $clause = $this->newClause('users', $query);

        $this->assertSame($query, $clause->from($table));
        $this->assertEquals($table, $query->table);
    }

    /**
     * @test
     */
    public function join_forwards_to_query()
    {
        $table = 'addresses';
        $testJoin = $this->newClause();

        $query = $this->mock(Query::class);
        $query->shouldReceive('join')->with($table)->andReturn($testJoin);

        $clause = $this->newClause('users', $query);

        $this->assertSame($testJoin, $clause->join($table));
        //$this->assertEquals($table, $query->table);
    }

    /**
     * @test
     */
    public function where_forwards_to_query()
    {
        $query = new Query();

        $clause = $this->newClause('users', $query);

        $this->assertSame($query, $clause->where('a', 'b'));
        $this->assertEquals('a', $query->conditions->first()->left);
        $this->assertEquals('b', $query->conditions->first()->right);
    }

    /**
     * @test
     */
    public function groupBy_forwards_to_query()
    {
        $params = ['a', 'b'];
        $query = new Query();

        $clause = $this->newClause('users', $query);

        $this->assertSame($query, $clause->groupBy(...$params));
        $this->assertEquals($params, $query->groupBys);
    }

    /**
     * @test
     */
    public function orderBy_forwards_to_query()
    {
        $query = new Query();

        $clause = $this->newClause('users', $query);

        $this->assertSame($query, $clause->orderBy('id', 'DESC'));
        $this->assertEquals(['id' => 'DESC'], $query->orderBys);
    }

    /**
     * @test
     */
    public function having_forwards_to_query()
    {
        $query = new Query();

        $clause = $this->newClause('users', $query);

        $this->assertSame($query, $clause->having('a', 'b'));
        $this->assertEquals('a', $query->havings->first()->left);
        $this->assertEquals('b', $query->havings->first()->right);
    }

    /**
     * @test
     */
    public function offset_forwards_to_query()
    {
        $query = new Query();

        $clause = $this->newClause('users', $query);

        $this->assertSame($query, $clause->offset(30, 10));
        $this->assertEquals(30, $query->offset);
        $this->assertEquals(10, $query->limit);
    }

    /**
     * @test
     */
    public function limit_forwards_to_query()
    {
        $query = new Query();

        $clause = $this->newClause('users', $query);

        $this->assertSame($query, $clause->limit(30, 10));
        $this->assertEquals(10, $query->offset);
        $this->assertEquals(30, $query->limit);
    }

    /**
     * @test
     */
    public function values_forwards_to_query()
    {
        $values = ['a' => 'b'];
        $query = new Query();

        $clause = $this->newClause('users', $query);

        $this->assertSame($query, $clause->values($values));
        $this->assertEquals($values, $query->values);
    }

    protected function newClause($table='', Query $query=null)
    {
        return new JoinClause($table, $query);
    }
}