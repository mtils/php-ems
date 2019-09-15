<?php
/**
 *  * Created by mtils on 25.08.19 at 09:26.
 **/

namespace Ems\Core\Connection;


use Ems\Contracts\Core\OutputConnection;
use Ems\Contracts\Core\Response;
use Ems\Contracts\Core\Stringable;
use Ems\Contracts\Core\Url as UrlContract;
use Ems\Contracts\Http\Response as HttpResponse;
use Ems\Core\Url;
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
     * Write the output. Usually just echo it
     *
     * @param string|Stringable $output
     * @param bool $lock
     *
     * @return mixed
     */
    public function write($output, $lock = false)
    {
        if (!$output instanceof Response) {
            echo $output;
            return true;
        }

        if ($output instanceof HttpResponse) {
            $this->outputHttpHeaders($output);
            echo $output->body();
            return null;
        }

        echo $output->payload();
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
     * @param HttpResponse $response
     */
    protected function outputHttpHeaders(HttpResponse $response)
    {
        if ($this->headersWereSent()) {
            return;
        }

        foreach ($response->headers() as $name=>$header) {
            $this->printHeader("$name: $header");
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
    protected function headersWereSent()
    {
        return is_bool($this->fakeSentHeaders) ? $this->fakeSentHeaders : headers_sent();
    }
}