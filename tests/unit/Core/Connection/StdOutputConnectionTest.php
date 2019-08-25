<?php
/**
 *  * Created by mtils on 25.08.19 at 14:10.
 **/

namespace unit\Core\Connection;


use Ems\Contracts\Core\OutputConnection;
use Ems\Core\Connection\StdOutputConnection;
use Ems\Http\Response as HttpResponse;
use Ems\TestCase;
use function ob_end_clean;
use function ob_get_contents;
use function ob_start;

class StdOutputConnectionTest extends TestCase
{

    /**
     * @test
     */
    public function it_implements_interface()
    {
        $this->assertInstanceOf(OutputConnection::class, $this->make());
    }

    /**
     * @test
     */
    public function open_opens_and_closes_connection()
    {
        $con = $this->make();
        $this->assertFalse($con->isOpen());
        $this->assertSame($con, $con->open());
        $this->assertTrue($con->isOpen());
        $this->assertSame($con, $con->close());

    }

    /**
     * @test
     */
    public function write_string()
    {
        $con = $this->make();
        ob_start();
        $con->write('Hello');
        $string = ob_get_contents();
        ob_end_clean();
        $this->assertEquals('Hello', $string);
    }

    /**
     * @test
     */
    public function write_http_response()
    {
        $con = $this->make();
        ob_start();

        $headers = [];

        $headerPrinter = function ($name, $replace=true) use (&$headers) {
            $headers[] = $name;
        };
        $con->outputHeaderBy($headerPrinter);

        $response = new HttpResponse([
            'foo' => 'bar'
        ], 'Hello');

        $con->fakeSentHeaders(false);

        $con->write($response);
        $string = ob_get_contents();
        ob_end_clean();
        $this->assertEquals('Hello', $string);
        $this->assertEquals('foo: bar', $headers[0]);
    }

    /**
     * @test
     */
    public function write_http_response_with_sent_headers()
    {
        $con = $this->make();
        ob_start();

        $headers = [];

        $headerPrinter = function ($name, $replace=true) use (&$headers) {
            $headers[] = $name;
        };
        $con->outputHeaderBy($headerPrinter);

        $response = new HttpResponse([
            'foo' => 'bar'
        ], 'Hello');

        $con->fakeSentHeaders(true);

        $con->write($response);
        $string = ob_get_contents();
        ob_end_clean();
        $this->assertEquals('Hello', $string);
        $this->assertCount(0, $headers);
    }

    protected function make()
    {
        return new StdOutputConnection();
    }
}