<?php
/**
 *  * Created by mtils on 21.10.18 at 14:36.
 **/

namespace Ems\Http;


use Ems\Contracts\Core\Configurable;
use Ems\Core\ConfigurableTrait;
use Ems\Core\Filesystem\FileStream;
use RuntimeException;
use function file_get_contents;

class HttpFileStream extends FileStream implements Configurable
{
    use ConfigurableTrait;

    /**
     * @var string
     */
    protected $method;

    /**
     * @var string
     */
    protected $header;

    /**
     * @var string
     */
    protected $content;

    /**
     * @var string
     */
    protected $protocolVersion = '1.1';

    /**
     * @var array
     */
    protected $defaultOptions = [
        'ignore_errors' => false,
        'max_redirects' => 5,
        'verify_peer'   => true,
        'verify_host'   => 2
    ];

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param string $method
     *
     * @return $this
     */
    public function setMethod($method)
    {
        $this->method = $method;
        return $this;
    }

    /**
     * @return string
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * @param string $header
     *
     * @return $this
     */
    public function setHeader($header)
    {
        $this->header = $header;
        return $this;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param string $content
     *
     * @return $this
     */
    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }

    /**
     * @return string
     */
    public function getProtocolVersion()
    {
        return $this->protocolVersion;
    }

    /**
     * @param string $protocolVersion
     *
     * @return $this
     */
    public function setProtocolVersion($protocolVersion)
    {
        $this->protocolVersion = $protocolVersion;
        return $this;
    }

    /**
     * @param string $path
     *
     * @return bool|resource
     */
    protected function openHandle($path)
    {
        return @fopen($path, $this->mode, false, $this->streamContext());
    }

    /**
     * {@inheritdoc}
     *
     * @return bool|string
     */
    protected function readAll()
    {
        $level = error_reporting(0);
        $url = (string)$this->url();
        $body = file_get_contents($url, 0, $this->streamContext());

        error_reporting($level);

        if ($body === false) {
            $error = error_get_last();
            $message = isset($error['message']) && $error['message'] ? $error['message'] : "Cannot read from $url.";
            throw new RuntimeException($message);
        }



        if (isset($http_response_header)) {
            $header = implode("\r\n", $http_response_header);
            return "$header\r\n\r\n$body";
        }

        return $body;
    }


    /**
     * @return resource
     */
    protected function streamContext()
    {
        return stream_context_create($this->streamContextArray());
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
                'header'           => $this->header,
                'content'          => $this->content,
                'protocol_version' => $this->protocolVersion,

                // options
                'ignore_errors'    => $this->getOption('ignore_errors'),
                'follow_location'  => $this->getOption('max_redirects') > 0,
                'max_redirects'    => $this->getOption('max_redirects') + 1,
                'timeout'          => $this->timeout === -1 ? 30 : $this->timeout
            ],
            'ssl' => [
                'verify_peer' => $this->getOption('verify_peer'),
                'verify_host' => $this->getOption('verify_host'),
            ]
        ];

    }

}