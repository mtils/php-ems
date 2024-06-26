<?php
/**
 *  * Created by mtils on 25.08.19 at 15:06.
 **/

namespace unit\Skeleton;


use Ems\Contracts\Routing\Input;
use Ems\Contracts\Skeleton\InputConnection;
use Ems\Skeleton\GlobalsHttpInputConnection;
use Ems\TestCase;
use PHPUnit\Framework\Attributes\Test;

class GlobalsHttpInputConnectionTest extends TestCase
{
    #[Test] public function it_implements_interface()
    {
        $this->assertInstanceOf(InputConnection::class, $this->make());
    }

    #[Test] public function isInteractive_returns_false()
    {
        $this->assertFalse($this->make()->isInteractive());
    }

    #[Test] public function read_reads_input()
    {
        $request = ['foo' => 'bar'];
        $con = $this->make($request, [
            'SERVER_PORT'   => 443,
            'HTTP_HOST'     => 'web-utils.de',
            'REQUEST_URI'   => 'test',
            'REQUEST_METHOD'=> 'GET'
        ]);

        $input = $con->read();
        $this->assertEquals($input->toArray(), $request);
    }

    #[Test] public function read_reads_input_into_handler()
    {
        $request = ['foo' => 'bar'];
        $con = $this->make($request, [
            'SERVER_PORT'   => 443,
            'HTTP_HOST'     => 'web-utils.de',
            'REQUEST_URI'   => 'test',
            'REQUEST_METHOD'=> 'GET'
        ]);

        $inputs = [];

        $handler = function (Input $input) use (&$inputs) {
            $inputs[] = $input;
        };

        $con->read($handler);
        $this->assertEquals($inputs[0]->toArray(), $request);
    }

    #[Test] public function open_and_close()
    {
        $con = $this->make();
        $this->assertFalse($con->isOpen());
        $con->open();
        $this->assertTrue($con->isOpen());
        $con->close();
    }

    protected function make($query=[], $server=[])
    {
        return new GlobalsHttpInputConnection($query, $server);
    }
}