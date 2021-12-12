<?php
/**
 *  * Created by mtils on 09.12.2021 at 21:02.
 **/

namespace Ems\Http;

use Ems\Contracts\Core\Input;
use Ems\TestCase;

use function range;

class HttpInputTest extends TestCase
{
    /**
     * @test
     */
    public function it_implements_interface()
    {
        $this->assertInstanceOf(Input::class, $this->newInput());
    }

    /**
     * @test
     */
    public function get_returns_value_from_any_source()
    {
        $data = ['foo' => 'bar'];
        $input = $this->newInput();
        $this->assertNull($input->get('foo'));

        $input = $this->newInput($data);
        $this->assertEquals($data['foo'], $input->get('foo'));

        $input = $this->newInput([], $data);
        $this->assertEquals($data['foo'], $input->get('foo'));

        $input = $this->newInput([], [], $data);
        $this->assertEquals($data['foo'], $input->get('foo'));

        $input = $this->newInput([], [], [], $data);
        $this->assertNull($input->get('foo'));

        $input = $this->newInput([], [], [], [], $data);
        $this->assertEquals($data['foo'], $input->get('foo'));

        $input = $this->newInput([], [], [], [], [], $data);
        $this->assertEquals($data['foo'], $input->get('foo'));
    }

    /**
     * @test
     */
    public function get_returns_value_prioritized()
    {
        $attributes = ['foo' => 'attributes'];
        $get = ['foo' => 'get'];
        $post = ['foo' => 'post'];
        $cookie = ['foo' => 'cookie'];
        $files = ['foo' => 'files'];
        $server = ['foo' => 'server'];

        $input = $this->newInput();
        $this->assertNull($input->get('foo'));

        $input = $this->newInput($get, $post, $cookie, $files, $server, $attributes);
        $this->assertEquals('attributes', $input['foo']);

        $input = $this->newInput($get, $post, $cookie, $files, $server);
        $this->assertEquals('get', $input['foo']);

        $input = $this->newInput([], $post, $cookie, $files, $server);
        $this->assertEquals('post', $input['foo']);

        $input = $this->newInput([], [], $cookie, $files, $server);
        $this->assertEquals('cookie', $input['foo']);

        $input = $this->newInput([], [], $cookie, $files, $server);
        $this->assertEquals('cookie', $input['foo']);

        $input = $this->newInput([], [], [], $files, $server);
        $this->assertEquals('server', $input['foo']);
    }

    /**
     * @test
     */
    public function offsetExists_returns_true_on_any_source()
    {
        $data = ['foo' => 'bar'];
        $input = $this->newInput();
        $this->assertFalse($input->offsetExists('foo'));

        $input = $this->newInput($data);
        $this->assertTrue($input->offsetExists('foo'));

        $input = $this->newInput([], $data);
        $this->assertTrue($input->offsetExists('foo'));

        $input = $this->newInput([], [], $data);
        $this->assertTrue($input->offsetExists('foo'));

        $input = $this->newInput([], [], [], $data);
        $this->assertFalse($input->offsetExists('foo'));

        $input = $this->newInput([], [], [], [], $data);
        $this->assertTrue($input->offsetExists('foo'));

        $input = $this->newInput([], [], [], [], [], $data);
        $this->assertTrue($input->offsetExists('foo'));
    }

    /**
     * @test
     */
    public function offsetUnset_removes_value_from_any_source()
    {
        $data = ['foo' => 'bar'];
        $input = $this->newInput();
        $this->assertFalse($input->offsetExists('foo'));

        $input = $this->newInput($data);
        $this->assertTrue($input->offsetExists('foo'));
        $input->offsetUnset('foo');
        $this->assertFalse($input->offsetExists('foo'));

        $input = $this->newInput([], $data);
        $this->assertTrue($input->offsetExists('foo'));
        $input->offsetUnset('foo');
        $this->assertFalse($input->offsetExists('foo'));

        $input = $this->newInput([], [], $data);
        $this->assertTrue($input->offsetExists('foo'));
        $input->offsetUnset('foo');
        $this->assertFalse($input->offsetExists('foo'));

        $input = $this->newInput([], [], [], $data);
        $this->assertFalse($input->offsetExists('foo'));

        $input = $this->newInput([], [], [], [], $data);
        $this->assertTrue($input->offsetExists('foo'));
        $input->offsetUnset('foo');
        $this->assertFalse($input->offsetExists('foo'));

        $input = $this->newInput([], [], [], [], [], $data);
        $this->assertTrue($input->offsetExists('foo'));
        $input->offsetUnset('foo');
        $this->assertFalse($input->offsetExists('foo'));
    }

    /**
     * @test
     */
    public function keys_returns_keys_from_every_source()
    {
        $attributes = ['a' => 'attributes'];
        $get = ['b' => 'get'];
        $post = ['c' => 'post'];
        $cookie = ['d' => 'cookie'];
        $files = ['z' => 'files'];
        $server = ['e' => 'server'];

        $input = $this->newInput($get, $post, $cookie, $files, $server, $attributes);
        $this->assertEquals(range('a', 'e'), $input->keys()->getSource());
    }

    /**
     * @test
     */
    public function toArray_returns_data_from_every_source()
    {
        $attributes = ['a' => 'attributes', 'attributes' => 'a'];
        $get = ['b' => 'get', 'get' => 'a'];
        $post = ['c' => 'post', 'post' => 'a'];
        $cookie = ['d' => 'cookie', 'cookie' => 'a'];
        $files = ['z' => 'files', 'files' => 'a'];
        $server = ['e' => 'server', 'server'=> 'a'];

        $input = $this->newInput($get, $post, $cookie, $files, $server, $attributes);
        $array = $input->toArray();
        $this->assertCount(10, $array);
        $this->assertEquals($cookie['cookie'], $array['cookie']);
    }

    protected function newInput(array $get=[], array $post=[], array $cookies=[], array $files=[], array $server=[], array $attributes=[]) : HttpInput
    {
        return new HttpInput($get, $post, $cookies, $files, $server, $attributes);
    }
}