<?php
/**
 *  * Created by mtils on 25.08.19 at 08:22.
 **/

namespace Ems\Core\Connection;


use Ems\Contracts\Core\Input as InputContract;
use Ems\Contracts\Core\InputConnection;
use Ems\Contracts\Core\Url as UrlContract;
use Ems\Core\Input;
use Ems\Core\Url;
use function fopen;

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
    protected $request;

    /**
     * @var array
     */
    protected $server;

    /**
     * GlobalsHttpInputConnection constructor.
     *
     * @param array $request (optional)
     * @param array $server (optional)
     */
    public function __construct($request=null, $server=null)
    {
        parent::__construct();
        $this->request = $request ?: $_REQUEST;
        $this->server = $server ?: $_SERVER;

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
     * @return Input
     */
    protected function createInput()
    {
        $input = new Input($this->request);
        $input->setMethod($this->server['REQUEST_METHOD']);
        $input->setUrl($this->createUrl());
        return $input;
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