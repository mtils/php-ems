<?php
/**
 *  * Created by mtils on 25.08.19 at 08:22.
 **/

namespace Ems\Skeleton;


use Ems\Contracts\Core\Url as UrlContract;
use Ems\Contracts\Routing\Input;
use Ems\Contracts\Routing\Input as InputContract;
use Ems\Contracts\Skeleton\InputConnection;
use Ems\Core\Connection\AbstractConnection;
use Ems\Core\Url;
use Ems\Routing\GenericInput;

use Ems\Routing\HttpInput;

use function fopen;
use function getallheaders;

class GlobalsHttpInputConnection extends AbstractConnection implements InputConnection
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
    protected $uri = 'php://stdin';

    /**
     * @var array
     */
    protected $query;

    /**
     * @var array
     */
    protected $server;
    /**
     * @var array
     */
    private $headers;
    /**
     * @var array
     */
    private $body;
    /**
     * @var array
     */
    private $cookies;
    /**
     * @var array
     */
    private $files;


    public function __construct(array $query=[], array $server=[], array $headers=[], array $body=[], array $cookies=[], array $files=[])
    {
        parent::__construct();
        $this->query = $query ?: $_GET;
        $this->server = $server ?: $_SERVER;
        $this->headers = $headers;
        $this->body = $body ?: $_POST;
        $this->cookies = $cookies ?: $_COOKIE;
        $this->files = $files ?: $_FILES;
        //print_r($this->files); print_r($_FILES); die();
    }

    /**
     * {@inheritDoc}
     *
     * @param callable|null $into
     *
     * @return InputContract
     */
    public function read(callable $into = null)
    {
        $input = $this->createInput();
        if ($into) {
            $into($input);
        }
        return $input;
    }

    /**
     * {@inheritDoc}
     *
     * @return bool
     */
    public function isInteractive()
    {
        return false;
    }

    /**
     * @param UrlContract $url
     *
     * @return resource
     */
    protected function createResource(UrlContract $url)
    {
        return fopen($this->uri, 'r');
    }

    /**
     * @return HttpInput
     */
    protected function createInput() : HttpInput
    {
        $attributes = [
            Input::FROM_QUERY       => $this->query,
            Input::FROM_BODY        => $this->body,
            Input::FROM_COOKIE      => $this->cookies,
            Input::FROM_SERVER      => $this->server,
            Input::FROM_FILES       => $this->files,
            'uri'                   => $this->createUrl(),
            'method'                => $this->server['REQUEST_METHOD'],
            'headers'               => $this->headers ?: getallheaders(),
            'determinedContentType' => 'text/html'
        ];
        return new HttpInput($attributes);
    }

    /**
     * @return Url
     */
    protected function createUrl()
    {
        $protocol = ((!empty($this->server['HTTPS']) && $this->server['HTTPS'] != 'off') || $this->server['SERVER_PORT'] == 443) ? "https://" : "http://";

        $url = $protocol . $this->server['HTTP_HOST'] . $this->server['REQUEST_URI'];

        return new Url($url);
    }
}