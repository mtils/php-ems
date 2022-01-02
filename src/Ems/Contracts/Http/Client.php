<?php
/**
 * Created by PhpStorm.
 * User: michi
 * Date: 10.11.17
 * Time: 17:36
 */

namespace Ems\Contracts\Http;

use Ems\Contracts\Core\Url;
use Ems\Core\ImmutableMessage;
use Ems\Http\HttpResponse;
use Psr\Http\Message\ResponseInterface;

interface Client
{
    /**
     * Perform a head request to $url. You await $contentType.
     *
     * @param Url  $url
     *
     * @return ResponseInterface|ImmutableMessage
     */
    public function head(Url $url) : ResponseInterface;

    /**
     * Perform a get request to $url. You await $contentType.
     *
     * @param Url  $url
     * @param null $contentType
     *
     * @return ResponseInterface|ImmutableMessage
     */
    public function get(Url $url, $contentType=null) : ResponseInterface;

    /**
     * Perform a post request. Post $data (raw). It should be send in
     * $contentType.
     *
     * @param Url    $url
     * @param mixed  $data
     * @param string $contentType (optional)
     *
     * @return ResponseInterface|ImmutableMessage
     */
    public function post(Url $url, $data=null, string $contentType=null) : ResponseInterface;

    /**
     * Perform a put request. Put $data (raw). It should be send in
     * $contentType.
     *
     * @param Url    $url
     * @param mixed  $data
     * @param string $contentType (optional)
     *
     * @return ResponseInterface|ImmutableMessage
     */
    public function put(Url $url, $data=null, string $contentType=null) : ResponseInterface;

    /**
     * Perform a patch request. Send $data (raw). It should be send in
     * $contentType.
     *
     * @param Url    $url
     * @param mixed  $data
     * @param string $contentType (optional)
     *
     * @return ResponseInterface|ImmutableMessage
     */
    public function patch(Url $url, $data=null, string $contentType=null) : ResponseInterface;

    /**
     * Perform a patch request. Send $data (raw). It should be send in
     * $contentType.
     *
     * @param Url    $url
     * @param mixed  $data
     * @param string $contentType (optional)
     *
     * @return ResponseInterface|ImmutableMessage
     */
    public function delete(Url $url, $data=null, string $contentType=null) : ResponseInterface;

    /**
     * Perform a post request. Post $data (raw). It should be send in
     * $contentType.
     *
     * @param Url    $url
     * @param array  $data
     * @param string $method (default: POST)
     *
     * @return ResponseInterface|ImmutableMessage
     */
    public function submit(Url $url, array $data, string $method=Connection::POST) : ResponseInterface;

    /**
     * Set headers before sending the request.
     *
     * @param string|array $header
     * @param string       $value (optional)
     *
     * @return self
     *
     * @example Client::headers()
     */
    public function header($header, string $value=null) : Client;
}