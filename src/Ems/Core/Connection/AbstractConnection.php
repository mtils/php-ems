<?php
/**
 *  * Created by mtils on 25.08.19 at 09:26.
 **/

namespace Ems\Core\Connection;


use Ems\Contracts\Core\Connection;
use Ems\Contracts\Core\Url as UrlContract;
use Ems\Core\Url;
use function fclose;
use function is_resource;

abstract class AbstractConnection implements Connection
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
    protected $uri = 'php://temp';

    /**
     * AbstractConnection constructor.
     *
     * @param string $uri (optional)
     */
    public function __construct($uri=null)
    {
        if ($uri) {
            $this->uri = $uri;
        }
        $this->url = new Url($this->uri);
    }

    /**
     * @param UrlContract $url
     *
     * @return resource
     */
    abstract protected function createResource(UrlContract $url);

    /**
     * {@inheritDoc}
     *
     * @return self
     **/
    public function open()
    {
        if (!$this->isOpen()) {
            $this->resource = $this->resource();
        }
        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @return self
     **/
    public function close()
    {
        if ($this->isOpen()) {
            fclose($this->resource);
        }
        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @return bool
     **/
    public function isOpen()
    {
        return is_resource($this->resource);
    }

    /**
     * {@inheritDoc}
     *
     * @return resource
     **/
    public function resource()
    {
        if (!$this->isOpen()) {
            $this->resource = $this->createResource($this->url());
        }
        return $this->resource;
    }

    /**
     * {@inheritDoc}
     *
     * @return UrlContract
     **/
    public function url()
    {
        return $this->url;
    }
}