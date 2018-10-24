<?php
/**
 * Created by PhpStorm.
 * User: michi
 * Date: 10.11.17
 * Time: 19:15
 */

namespace Ems\Http;

use Ems\Contracts\Core\Configurable;
use Ems\Contracts\Core\HasMethodHooks;
use Ems\Contracts\Core\Url as UrlContract;
use Ems\Contracts\Http\Connection as HttpConnection;
use Ems\Core\ConfigurableTrait;
use Ems\Core\FilesystemConnection as BaseConnection;
use Ems\Core\Patterns\HookableTrait;
use Ems\Core\Url;

class FilesystemConnection extends BaseConnection implements HttpConnection, Configurable, HasMethodHooks
{
    use ConfigurableTrait;
    use HookableTrait;

    /**
     * @var string
     */
    protected $user = '';

    /**
     * @var string
     */
    protected $password = '';

    /**
     * @var array
     */
    protected $defaultOptions = [
        'ignore_errors' => false,
        'max_redirects' => 5,
        'timeout'       => 5,
        'verify_peer'   => true,
        'verify_host'   => 2
    ];

    /**
     * FilesystemConnection constructor.
     *
     * @param UrlContract|string $url
     */
    public function __construct($url)
    {
        $url = $url instanceof UrlContract ? $url : new Url($url);
        parent::__construct($this->popCredentials($url));
    }

    /**
     * Send a http request with file_file_get_contents.
     *
     * @param string      $method
     * @param array       $headers (optional)
     * @param null|string $content
     * @param string      $protocolVersion (default: '1.1')
     *
     * @return Response
     */
    public function send($method, array $headers = [], $content = null, $protocolVersion = '1.1')
    {

        $headers = $this->addCredentials($headers);

        $this->callBeforeListeners('send', [$this->url, $method, &$headers]);

        // We have to recreate the stream every time
        $this->close();

        $header = implode("\r\n", $headers);
        $stream = $this->createHttpStream($method, $header, $content, $protocolVersion);

        $this->stream = $stream;

        // Currently only stream-less connections are supported
        $raw = $stream->toString();

        list($responseHeader, $content) = $this->parseMessage($raw);

        $response = new Response($responseHeader, $content);
        $response->setRaw($raw);

        $this->callAfterListeners('send', [$this->url, $method, &$headers, $response]);

        $this->close();

        return $response;

    }

    /**
     * @return array
     */
    public function methodHooks()
    {
        return ['send'];
    }

    /**
     * Splits the received message in header and body.
     *
     * @param string $message
     * @return array
     */
    protected function parseMessage($message)
    {
        $headerAndBody = explode("\r\n\r\n", $message, 2);
        $headers = explode("\r\n", $headerAndBody[0]);

        return [$this->parseHeader($headers), $headerAndBody[1]];

    }

    /**
     * Parses the automatically assigned $http_response_header of stream wrappers.
     *
     * @param mixed $responseHeader
     *
     * @return array
     */
    protected function parseHeader($responseHeader)
    {

        $filtered = [];

        foreach ((array)$responseHeader as $header) {
            if (stripos($header, 'http/')) {
                $filtered = [];
            }
            $filtered[] = $header;
        }

        return $filtered;
    }

    /**
     * Remove the credentials from url and assign them to
     *
     * @param UrlContract $url
     *
     * @return UrlContract
     */
    protected function popCredentials(UrlContract $url)
    {
        if ($url->user && $url->password) {
            $this->user = $url->user;
            $this->password = $url->password;
            return $url->user('')->password('');
        }

        $this->user = '';
        $this->password = '';

        return $url;

    }

    /**
     * Add the assigned credentials as Basic Auth headers.
     *
     * @param array $headers
     *
     * @return array
     */
    protected function addCredentials(array $headers)
    {

        if (!$this->user || !$this->password) {
            return $headers;
        }

        // Check if credentials are already in headers
        foreach ($headers as $line) {
            if (stripos(trim($line),'authorization: basic') === 0) {
                return $headers;
            }
        }

        $auth = $this->user . ':' . $this->password;

        $headers[] = 'Authorization: Basic ' . base64_encode("$auth");

        return $headers;

    }

    /**
     * This method is only called if you asking for the resource or open without
     * calling send().
     * Calling send() will create a stream on demand which is only active until
     * the response was completely received.
     *
     * @return HttpFileStream
     */
    protected function createStream()
    {
        return $this->createHttpStream('GET');
    }

    /**
     * @param string $method
     * @param string $header (optional)
     * @param string $content (optional)
     * @param string $protocolVersion (default:'1.1')
     *
     * @return HttpFileStream
     */
    protected function createHttpStream($method, $header = '', $content = null, $protocolVersion = '1.1')
    {
        $stream = new HttpFileStream($this->url());

        $stream->setMethod($method)
            ->setHeader($header)
            ->setProtocolVersion($protocolVersion);

        if ($content !== null) {
            $stream->setContent($content);
        }

        foreach ($this->supportedOptions() as $key) {
            $stream->setOption($key, $this->getOption($key));
        }

        return $stream;

    }

}