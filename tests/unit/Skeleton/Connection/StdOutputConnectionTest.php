<?php
/**
 *  * Created by mtils on 25.08.19 at 14:10.
 **/

namespace unit\Skeleton\Connection;


use Ems\Contracts\Http\Cookie;
use Ems\Contracts\Skeleton\OutputConnection;
use Ems\Core\Response;
use Ems\Skeleton\Connection\StdOutputConnection;
use Ems\Http\HttpResponse;
use Ems\TestCase;
use PHPUnit\Framework\Attributes\Test;

use function ob_end_clean;
use function ob_get_contents;
use function ob_start;
use function strpos;
use function substr;

class StdOutputConnectionTest extends TestCase
{

    #[Test] public function it_implements_interface()
    {
        $this->assertInstanceOf(OutputConnection::class, $this->make());
    }

    #[Test] public function open_opens_and_closes_connection()
    {
        $con = $this->make();
        $this->assertFalse($con->isOpen());
        $this->assertSame($con, $con->open());
        $this->assertTrue($con->isOpen());
        $this->assertSame($con, $con->close());

    }

    #[Test] public function write_string()
    {
        $con = $this->make();
        ob_start();
        $con->write('Hello');
        $string = ob_get_contents();
        ob_end_clean();
        $this->assertEquals('Hello', $string);
    }

    #[Test] public function write_http_response()
    {
        $con = $this->make();
        ob_start();

        $headers = [];

        $headerPrinter = function ($header, $replace=true) use (&$headers) {
            $headers[] = $header;
        };
        $con->outputHeaderBy($headerPrinter);

        $response = new HttpResponse('Hello', [
            'foo: bar'
        ]);

        $con->fakeSentHeaders(false);

        $con->write($response);
        $string = ob_get_contents();
        ob_end_clean();
        $this->assertEquals('Hello', $string);
        $found = false;
        foreach ($headers as $header) {
            if ($header == 'foo: bar') {
                $found = true;
            }
        }
        $this->assertTrue($found, 'Added header was in response');
    }

    #[Test] public function write_http_response_with_sent_headers()
    {
        $con = $this->make();
        ob_start();

        $headers = [];

        $headerPrinter = function ($name, $replace=true) use (&$headers) {
            $headers[] = $name;
        };
        $con->outputHeaderBy($headerPrinter);

        $response = new HttpResponse('Hello', [
            'foo: bar'
        ]);

        $con->fakeSentHeaders(true);

        $con->write($response);
        $string = ob_get_contents();
        ob_end_clean();
        $this->assertEquals('Hello', $string);
        $this->assertCount(0, $headers);
    }

    #[Test] public function write_status_line_without_phrase()
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Content-Encoding' => 'gzip'
        ];
        $response = new HttpResponse('Nothing is here', $headers, 404);

        list($headerLines, $body) = $this->render($response);

        $this->assertStringStartsWith('HTTP/', $headerLines[0]);
        $this->assertStringContainsString($response->protocolVersion, $headerLines[0]);
        $this->assertStringContainsString((string)$response->status, $headerLines[0]);
        $this->assertStringNotContainsString('Found', $headerLines[0]);

        $this->assertEquals("$response", $body);
    }

    #[Test] public function write_status_line_with_phrase()
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Content-Encoding' => 'gzip'
        ];
        $response = new HttpResponse('Nothing is here', $headers);
        $response = $response->withStatus(404, 'Nothing found here');

        list($headerLines, $body) = $this->render($response);

        $this->assertStringStartsWith('HTTP/', $headerLines[0]);
        $this->assertStringContainsString($response->protocolVersion, $headerLines[0]);
        $this->assertStringContainsString((string)$response->status, $headerLines[0]);
        $this->assertStringContainsString($response->getReasonPhrase(), $headerLines[0]);
        $this->assertEquals("$response", $body);
    }

    #[Test] public function write_cookie_into_headers()
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Content-Encoding' => 'gzip'
        ];
        $response = new HttpResponse('Success', $headers);
        $response = $response->withCookie('foo', 'bar')
        ->withCookie('test', 'content', 90, '/users','localhost', true, true, Cookie::STRICT);

        list($headerLines, $body) = $this->render($response);

        $cookieHeaders = $this->cookieHeaders($headerLines);

        $this->assertStringContainsString('bar', $cookieHeaders['foo']);
        $this->assertStringContainsString('content', $cookieHeaders['test']);
        $this->assertStringContainsString('expires', $cookieHeaders['test']);
        $this->assertStringContainsString('Max-Age', $cookieHeaders['test']);
        $this->assertStringContainsString('/users', $cookieHeaders['test']);
        $this->assertStringContainsString('localhost', $cookieHeaders['test']);
        $this->assertStringContainsString('secure', $cookieHeaders['test']);
        $this->assertStringContainsString('httponly', $cookieHeaders['test']);
        $this->assertStringContainsString(Cookie::STRICT, $cookieHeaders['test']);


        $this->assertEquals("$response", $body);
    }

    #[Test] public function write_cookies_into_headers()
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Content-Encoding' => 'gzip'
        ];
        $response = new HttpResponse('Success', $headers);
        $response = $response->withCookie('foo', 'bar');

        list($headerLines, $body) = $this->render($response);

        $cookieHeaders = $this->cookieHeaders($headerLines);

        $this->assertStringContainsString('bar', $cookieHeaders['foo']);
        $this->assertEquals("$response", $body);
    }

    protected function render(Response $response)
    {
        $con = $this->make();
        ob_start();

        $headers = [];
        $headerPrinter = function ($name, $replace=true) use (&$headers) {
            $headers[] = $name;
        };

        $con->outputHeaderBy($headerPrinter);
        $con->fakeSentHeaders(false);

        $con->write($response);
        $string = ob_get_contents();
        ob_end_clean();
        return [$headers, $string];
    }

    protected function cookieHeaders(array $headers) : array
    {
        $cookieHeaders = [];
        foreach ($headers as $line) {
            if (strpos($line,'Set-Cookie:') !== 0) {
                continue;
            }
            $cookieLine = trim(substr($line, 11));
            $parts = explode('=', $cookieLine, 2);
            $cookieHeaders[$parts[0]] = $cookieLine;
        }
        return $cookieHeaders;
    }

    protected function make() : StdOutputConnection
    {
        return new StdOutputConnection();
    }
}