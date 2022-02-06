<?php
/**
 *  * Created by mtils on 25.08.19 at 09:26.
 **/

namespace Ems\Skeleton\Connection;


use Ems\Contracts\Core\Stringable;
use Ems\Contracts\Core\Url as UrlContract;
use Ems\Contracts\Http\Cookie;
use Ems\Contracts\Skeleton\OutputConnection;
use Ems\Core\Connection\AbstractConnection;
use Ems\Core\Response;
use Ems\Core\Url;
use Ems\Http\HttpResponse;
use Ems\Http\Serializer\CookieSerializer;
use Psr\Http\Message\ResponseInterface;

use function call_user_func;
use function fopen;
use function headers_sent;
use function is_bool;

class StdOutputConnection extends AbstractConnection implements OutputConnection
{
    /**
     * @var Url
     */
    protected $url;

    /**
     * @var resource
     */
    protected $resource;

    /**
     * @var string
     */
    protected $uri = 'php://stdout';

    /**
     * @var bool|null
     */
    protected $fakeSentHeaders;

    /**
     * @var callable
     */
    protected $headerPrinter;

    /**
     * @var callable
     */
    protected $cookieSerializer;

    /**
     * Write the output. Usually just echo it
     *
     * @param string|Stringable $output
     * @param bool $lock
     *
     * @return mixed
     */
    public function write($output, bool $lock = false)
    {
        if (!$output instanceof Response) {
            echo $output;
            return true;
        }

        if (!$output instanceof ResponseInterface) {
            $this->outputHttpHeadersForCoreResponse($output);
            echo $output->payload;
            return null;
        }

        $this->outputHttpHeaders($output);
        echo $output->getBody();
        return null;

    }

    /**
     * @param callable $headerPrinter
     *
     * @return $this
     */
    public function outputHeaderBy(callable $headerPrinter)
    {
        $this->headerPrinter = $headerPrinter;
        return $this;
    }

    /**
     * @param bool $fake
     *
     * @return self
     */
    public function fakeSentHeaders($fake)
    {
        $this->fakeSentHeaders = $fake;
        return $this;
    }

    /**
     * Set the cookie serializer.
     *
     * @param callable $cookieSerializer
     * @return $this
     */
    public function serializeCookieBy(callable $cookieSerializer)
    {
        $this->cookieSerializer = $cookieSerializer;
        return $this;
    }

    /**
     * Mimic the output header for a non-http responses.
     *
     * @param Response $response
     * @return void
     */
    protected function outputHttpHeadersForCoreResponse(Response $response)
    {
        if ($this->headersWereSent()) {
            return;
        }

        if ($response->status > 299 && $response->status < 600) {
            $this->printHeader($this->buildStatusLine($response->status));
        }
    }

    /**
     * @param ResponseInterface $response
     */
    protected function outputHttpHeaders(ResponseInterface $response)
    {
        if ($this->headersWereSent()) {
            return;
        }

        $this->printHeader($this->getStatusLine($response));

        foreach ($response->getHeaders() as $key=>$lines) {
            foreach ($lines as $header) {
                $this->printHeader("$key: $header");
            }
        }

        if (!$response instanceof HttpResponse) {
            return;
        }

        foreach ($response->cookies as $cookie) {
            $this->printHeader('Set-Cookie: ' . $this->serializeCookie($cookie));
        }
    }

    /**
     * @param UrlContract $url
     *
     * @return resource
     */
    protected function createResource(UrlContract $url)
    {
        return fopen($this->uri, 'w');
    }

    /**
     * @param string $name
     *@param bool $replace
     */
    protected function printHeader($name, $replace=true)
    {
        $handler = $this->headerPrinter ?: 'header';
        call_user_func($handler, $name, $replace);
    }

    /**
     * @return bool
     */
    protected function headersWereSent() : bool
    {
        return is_bool($this->fakeSentHeaders) ? $this->fakeSentHeaders : headers_sent();
    }

    /**
     * @param Cookie $cookie
     * @return string
     */
    protected function serializeCookie(Cookie $cookie) : string
    {
        if (!$this->cookieSerializer) {
            $this->serializeCookieBy(new CookieSerializer());
        }
        return call_user_func($this->cookieSerializer, $cookie);
    }

    /**
     * @param ResponseInterface $response
     * @return string
     */
    protected function getStatusLine(ResponseInterface $response) : string
    {
        $protocolVersion = $response->getProtocolVersion() ?: '1.1';
        $statusCode = $response->getStatusCode() ?: 200;
        $statusPhrase = $response->getReasonPhrase() ?: '';
        return $this->buildStatusLine($statusCode, $protocolVersion, $statusPhrase);

    }

    /**
     * Build the http status line
     *
     * @param int $status
     * @param string $protocolVersion
     * @param string $reasonPhrase
     *
     * @return string
     */
    protected function buildStatusLine(int $status=200, string $protocolVersion='1.1', string $reasonPhrase='') : string
    {
        return trim("HTTP/$protocolVersion $status $reasonPhrase");
    }
}