<?php

namespace Ems\Cache\Storage;

use Ems\Contracts\Cache\Storage;
use DateTime;
use Ems\Testing\LoggingCallable;
use Mockery as m;

class ArrayStorageTest extends \Ems\TestCase
{
    public function test_implements_interface()
    {
        $this->assertInstanceOf(Storage::class, $this->newStorage());
    }

    public function test_escape_returns_nonempty_string()
    {
        $this->assertTrue(is_string($this->newStorage()->escape('foo')));
        $this->assertTrue(strlen($this->newStorage()->escape('foo')) > 2);
    }

    public function test_get_returns_putted_value()
    {
        $storage = $this->newStorage();
        $this->assertNull($storage->get('foo'));
        $storage->put('foo', 'bar', ['a', 'b']);
        $this->assertEquals('bar', $storage->get('foo'));
    }

    public function test_several_returns_putted_values()
    {
        $storage = $this->newStorage();
        $this->assertNull($storage->get('foo'));
        $storage->put('foo', 'bar');
        $storage->put('foo2', 'bor');
        $this->assertEquals(['foo' => 'bar', 'foo2' => 'bor'], $storage->several(['foo', 'foo2']));
    }

    public function test_increment_increments_number()
    {
        $storage = $this->newStorage();
        $this->assertEquals(1, $storage->increment('foo', 1));
        $this->assertEquals(1, $storage->increment('bar', 1));
        $this->assertEquals(2, $storage->increment('foo', 1));
        $this->assertEquals(4, $storage->increment('foo', 2));
    }

    public function test_increment_and_decrement_number()
    {
        $storage = $this->newStorage();
        $this->assertEquals(1, $storage->increment('foo', 1));
        $this->assertEquals(1, $storage->increment('bar', 1));
        $this->assertEquals(2, $storage->increment('foo', 1));
        $this->assertEquals(4, $storage->increment('foo', 2));
        $this->assertEquals(3, $storage->decrement('foo', 1));

        $this->assertEquals(-1, $storage->decrement('php', 1));
    }

    public function test_clear_deletes_all_putted_values()
    {
        $storage = $this->newStorage();
        $this->assertNull($storage->get('foo'));
        $storage->put('foo', 'bar', ['a', 'b']);
        $this->assertEquals('bar', $storage->get('foo'));
        $storage->put('foo2', 'bar2', ['a', 'b']);
        $this->assertEquals('bar2', $storage->get('foo2'));

        $storage->clear();
        $this->assertNull($storage->get('foo'));
        $this->assertNull($storage->get('foo2'));
    }

    public function test_forget_deletes_putted_values()
    {
        $storage = $this->newStorage();
        $this->assertNull($storage->get('foo'));

        $storage->put('foo', 'bar', ['a', 'b']);
        $this->assertEquals('bar', $storage->get('foo'));

        $storage->put('foo2', 'bar2', ['a', 'b']);
        $this->assertEquals('bar2', $storage->get('foo2'));

        $storage->forget('foo');
        $this->assertNull($storage->get('foo'));
        $this->assertEquals('bar2', $storage->get('foo2'));
        $storage->forget('foo2');
        $this->assertNull($storage->get('foo2'));
    }

    public function test_prune_deletes_tagged_values()
    {
        $storage = $this->newStorage();
        $this->assertNull($storage->get('foo'));

        $storage->put('foo', 'bar', ['a', 'b']);
        $this->assertEquals('bar', $storage->get('foo'));

        $storage->put('foo2', 'bar2', ['c', 'd']);
        $this->assertEquals('bar2', $storage->get('foo2'));

        $storage->put('foo3', 'bar3', ['b']);
        $this->assertEquals('bar3', $storage->get('foo3'));

        $storage->prune(['b', 'e']);

        $this->assertNull($storage->get('foo'));
        $this->assertEquals('bar2', $storage->get('foo2'));
        $this->assertNull($storage->get('foo3'));
    }

    protected function newStorage()
    {
        return new ArrayStorage();
    }
}
