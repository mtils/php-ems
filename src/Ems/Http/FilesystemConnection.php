<?php
/**
 * Created by PhpStorm.
 * User: michi
 * Date: 10.11.17
 * Time: 19:15
 */

namespace Ems\Http;

use Ems\Contracts\Core\Configurable;
use Ems\Contracts\Core\Filesystem;
use Ems\Contracts\Core\HasMethodHooks;
use Ems\Contracts\Core\Url as UrlContract;
use Ems\Contracts\Http\Connection as HttpConnection;
use Ems\Core\ConfigurableTrait;
use Ems\Core\FilesystemConnection as BaseConnection;
use Ems\Core\Patterns\HookableTrait;

class FilesystemConnection extends BaseConnection implements HttpConnection, Configurable, HasMethodHooks
{
    use ConfigurableTrait;
    use HookableTrait;

    /**
     * @var string
     */
    protected $method = '';

    /**
     * @var array
     */
    protected $headers = [];

    /**
     * @var string
     */
    protected $content = '';

    /**
     * @var string
     */
    protected $protocolVersion = '1.1';

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
        'ignore_errors' => true,
        'max_redirects' => 5,
        'timeout'       => 5,
        'verify_peer'   => true,
        'verify_host'   => 2
    ];

    /**
     * FilesystemConnection constructor.
     *
     * @param UrlContract        $url
     * @param Filesystem $filesystem (optional)
     */
    public function __construct(UrlContract $url, Filesystem $filesystem=null)
    {
        parent::__construct($this->popCredentials($url), $filesystem);
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
        $this->callBeforeListeners('send', [$this->url, $method, &$headers]);

        $this->method = $method;
        $this->headers = $headers;
        $this->content = $content;
        $this->protocolVersion = $protocolVersion;
        $raw = $this->read();

        list($responseHeader, $content) = $this->parseMessage($raw);

        $response = new Response($responseHeader, $content);
        $response->setRaw($raw);

        $this->callAfterListeners('send', [$this->url, $method, &$headers]);

        return $response;

    }

    /**
     * @return resource
     *
     * @throws \Ems\Contracts\Core\Errors\Unsupported
     */
    public function resource()
    {
        if (!$this->resource) {
            $this->resource = $this->filesystem->handle($this->streamContextArray());
        }
        return $this->resource;
    }

    /**
     * @return array
     */
    public function methodHooks()
    {
        return ['send'];
    }

    /**
     * Devides the received message in header and body.
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
     * Build the beautiful array for stream_context_create
     *
     * @return array
     *
     * @throws \Ems\Contracts\Core\Errors\UnSupported
     */
    protected function streamContextArray()
    {
        return [
            'http' => [
                // direct connection properties
                'method'           => $this->method,
                'header'           => implode("\r\n", $this->addCredentials($this->headers)),
                'content'          => $this->content,
                'protocol_version' => $this->protocolVersion,

                // options
                'ignore_errors'    => $this->getOption('ignore_errors'),
                'follow_location'  => $this->getOption('max_redirects') > 0,
                'max_redirects'    => $this->getOption('max_redirects') + 1,
                'timeout'          => $this->getOption('timeout')
            ],
            'ssl' => [
                'verify_peer' => $this->getOption('verify_peer'),
                'verify_host' => $this->getOption('verify_host'),
            ]
        ];

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
}