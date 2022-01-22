<?php
/**
 *  * Created by mtils on 16.01.2022 at 21:02.
 **/

namespace Ems\Routing;

use Ems\Contracts\Core\Serializer;
use Ems\Contracts\Core\Storage;
use Ems\Routing\SessionHandler\ArraySessionHandler;
use Ems\Contracts\Routing\Session as SessionContract;
use Ems\TestCase;
use LogicException;
use SessionHandlerInterface;

use stdClass;

use UnexpectedValueException;

use function serialize;
use function time;
use function unserialize;

class SessionTest extends TestCase
{
    /**
     * @test
     */
    public function it_implements_interface()
    {
        $this->assertInstanceOf(SessionContract::class, $this->session());
    }

    /**
     * @test
     */
    public function isStarted_returns_right_state()
    {
        $session = $this->session();
        $this->assertFalse($session->isStarted());
        $this->assertTrue($session->start());
        $this->assertTrue($session->isStarted());

        $this->expectException(LogicException::class);
        $session->start();
    }

    /**
     * @test
     */
    public function session_starts_when_checking_data()
    {
        $session = $this->session();
        $this->assertFalse($session->isStarted());
        $this->assertFalse(isset($session['foo']));
        $this->assertTrue($session->isStarted());
    }

    /**
     * @test
     */
    public function session_starts_when_accessing_data()
    {
        $session = $this->session();
        $this->assertFalse($session->isStarted());
        $session['foo'] = 'bar';
        $this->assertEquals('bar', $session['foo']);
        $this->assertTrue($session->isStarted());

        $session = $this->session();
        $this->assertFalse($session->isStarted());
        $this->assertSame([], $session->toArray());
        $this->assertTrue($session->isStarted());
    }

    /**
     * @test
     */
    public function session_starts_when_deleting_data()
    {
        $session = $this->session();
        $this->assertFalse($session->isStarted());
        unset($session['foo']);
        $this->assertTrue($session->isStarted());

        $session = $this->session();
        $this->assertFalse($session->isStarted());
        $session->clear();
        $this->assertTrue($session->isStarted());
    }

    /**
     * @test
     */
    public function get_and_set_data()
    {
        $id = 'ABCD';
        $data = ['foo' => 'bar'];
        $sessions = [
            $id => [
                'data' => serialize($data),
                'updated' => time()
            ]
        ];

        $handler = new ArraySessionHandler($sessions);

        $session = $this->session($handler);
        $session->setId($id);
        $this->assertEquals('bar', $session['foo']);

        $session['a'] = 'b';
        $this->assertEquals($sessions, $handler->toArray());
        $session->clear(['foo']);
        $session->persist();
        $handlerData = $handler->toArray();
        $sessionData = unserialize($handlerData[$id]['data']);
        $this->assertEquals(['a'=>'b'], $sessionData);
    }

    /**
     * @test
     */
    public function clear_deletes_keys()
    {
        $session = $this->session();
        $session['a'] = 'b';
        $session['c'] = 'd';
        $session['e'] = 'f';
        $this->assertEquals(['a'=>'b','c'=>'d', 'e'=>'f'], $session->toArray());
        $session->clear(['a','e']);
        $this->assertEquals(['c'=>'d'], $session->toArray());
    }

    /**
     * @test
     */
    public function clear_deletes_all()
    {
        $session = $this->session();
        $session['a'] = 'b';
        $session['c'] = 'd';
        $session['e'] = 'f';
        $this->assertEquals(['a'=>'b','c'=>'d', 'e'=>'f'], $session->toArray());
        $session->clear();
        $this->assertSame([], $session->toArray());
    }

    /**
     * @test
     */
    public function clear_does_not_delete_if_empty_array_passed()
    {
        $session = $this->session();
        $session['a'] = 'b';
        $session['c'] = 'd';
        $session['e'] = 'f';
        $this->assertEquals(['a'=>'b','c'=>'d', 'e'=>'f'], $session->toArray());
        $session->clear([]);
        $this->assertEquals(['a'=>'b','c'=>'d', 'e'=>'f'], $session->toArray());
    }

    /**
     * @test
     */
    public function empty_data_results_in_session_destroy()
    {
        $id = 'ABCD';
        $data = serialize(['a' => 'b']);

        $handler = $this->mock(SessionHandlerInterface::class);
        $handler->shouldReceive('read')->with($id)->andReturn($data);

        $session = $this->session($handler)->setId($id);
        $session['foo'] = 'bar';
        $this->assertEquals('b', $session['a']);
        $this->assertEquals('bar', $session['foo']);

        $handler->shouldReceive('destroy')
            ->with($id)
            ->once()
            ->andReturn(true);

        $session->clear();
        $session->persist();

    }

    /**
     * @test
     */
    public function it_exposes_itself_as_buffered()
    {
        $this->assertTrue($this->session()->isBuffered());
    }

    /**
     * @test
     */
    public function it_exposes_itself_as_utility_storage()
    {
        $this->assertEquals(Storage::UTILITY, $this->session()->storageType());
    }

    protected function session(SessionHandlerInterface $handler=null, Serializer $serializer=null) : Session
    {
        $data = [];
        $handler = $handler ?: new ArraySessionHandler($data);
        return new Session($handler, $serializer);
    }

    /**
     * @test
     */
    public function it_supports_custom_id_generator()
    {
        $session = $this->session();
        $id = 'ABCD';
        $session->setIdGenerator(function () use ($id) {
            return $id;
        });
        $session['foo'] = 'bar';
        $this->assertEquals($id, $session->getId());
    }

    /**
     * @test
     */
    public function it_throws_exception_if_handler_returns_no_array()
    {
        $id = 'ABCD';
        $data = serialize(new stdClass());
        $handler = $this->mock(SessionHandlerInterface::class);
        $handler->shouldReceive('read')->with($id)->andReturn($data);

        $session = $this->session($handler);
        $session->setId($id);
        $this->expectException(UnexpectedValueException::class);
        $this->assertEmpty($session['foo']);
    }
}
