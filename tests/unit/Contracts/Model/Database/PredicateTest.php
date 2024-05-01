<?php
/**
 *  * Created by mtils on 22.02.20 at 11:30.
 **/

namespace Ems\Contracts\Model\Database;


use Ems\TestCase;
use PHPUnit\Framework\Attributes\Test;

class PredicateTest extends TestCase
{

    #[Test] public function it_instantiates()
    {
        $this->assertInstanceOf(Predicate::class, $this->newPredicate());
    }

    #[Test] public function construct_with_one_arg()
    {
        $predicate = $this->newPredicate('name');
        $this->assertEquals('name', $predicate->left);
        $this->assertSame('', $predicate->operator);
        $this->assertNull($predicate->right);
    }

    #[Test] public function construct_with_two_args()
    {
        $predicate = $this->newPredicate('name', 'John');
        $this->assertEquals('name', $predicate->left);
        $this->assertEquals('=', $predicate->operator);
        $this->assertEquals('John', $predicate->right);
    }

    #[Test] public function construct_with_three_args()
    {
        $predicate = $this->newPredicate('age', '>', 5);
        $this->assertEquals('age', $predicate->left);
        $this->assertEquals('>', $predicate->operator);
        $this->assertEquals(5, $predicate->right);
    }

    #[Test] public function get_and_set_rightIsKey()
    {
        $predicate = $this->newPredicate('age', '>', 5);
        $this->assertFalse($predicate->rightIsKey);
        $this->assertSame($predicate, $predicate->rightIsKey(true));
        $this->assertTrue($predicate->rightIsKey);
    }

    #[Test] public function __get_unknown_property()
    {
        $predicate = $this->newPredicate('age', '>', 5);
        $this->assertNull($predicate->__get('something'));
    }

    /**
     * @param array $args
     *
     * @return Predicate
     */
    protected function newPredicate(...$args)
    {
        return new Predicate(...$args);
    }
}