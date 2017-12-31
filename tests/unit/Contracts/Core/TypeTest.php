<?php
/**
 *  * Created by mtils on 17.12.17 at 11:13.
 **/

namespace Ems\Contracts\Core;


use ArrayIterator;

use Ems\Core\Collections\OrderedList;
use Ems\TestCase;
use Ems\XType\Eloquent\Order;
use Traversable;

class TypeTest extends TestCase
{

    public function test_is_returns_true_on_null_if_nullable()
    {
        $this->assertTrue(Type::is(null, 'string', true));
        $this->assertFalse(Type::is(null, 'string'));
    }

    public function test_is_returns_true_if_all_types_matches()
    {
        $this->assertTrue(Type::is(15, ['numeric', 'int']));
        $this->assertFalse(Type::is(15, ['numeric', 'float']));
    }

    public function test_is_returns_true_if_traversable()
    {
        $this->assertTrue(Type::is(new ArrayIterator(), Traversable::class));
        $this->assertTrue(Type::is([], Traversable::class));
    }

    public function test_toBool_returns_right_values()
    {
        $this->assertTrue(Type::toBool('') === false);
        $list = new OrderedList([1,2,3]);
        $this->assertTrue(Type::toBool($list) === true);
        $this->assertTrue(Type::toBool(new OrderedList()) === false);
        $this->assertTrue(Type::toBool(' ') === false);
        $this->assertTrue(Type::toBool('0') === false);
        $this->assertTrue(Type::toBool(false) === false);
        $this->assertTrue(Type::toBool('false') === false);
        $this->assertTrue(Type::toBool('true') === true);
        $this->assertTrue(Type::toBool(new \Ems\Core\Url()) === false);

    }

    /**
     * @expectedException \Ems\Contracts\Core\Exceptions\TypeException
     */
    public function test_force_throws_exception_if_type_does_not_match()
    {
        Type::force(145, 'string');
    }
}