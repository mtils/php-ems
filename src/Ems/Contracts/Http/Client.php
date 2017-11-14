<?php
/**
 * Created by PhpStorm.
 * User: michi
 * Date: 10.11.17
 * Time: 17:36
 */

namespace Ems\Contracts\Http;

use Ems\Contracts\Core\Url;

interface Client
{
    /**
     * Perform a head request to $url. You await $contentType.
     *
     * @param Url  $url
     *
     * @return Response
     */
    public function head(Url $url);

    /**
     * Perform a get request to $url. You await $contentType.
     *
     * @param Url  $url
     * @param null $contentType
     *
     * @return Response
     */
    public function get(Url $url, $contentType=null);

    /**
     * Perform a post request. Post $data (raw). It should be send in
     * $contentType.
     *
     * @param Url    $url
     * @param mixed  $data
     * @param string $contentType (optional)
     *
     * @return Response
     */
    public function post(Url $url, $data=null, $contentType=null);

    /**
     * Perform a put request. Put $data (raw). It should be send in
     * $contentType.
     *
     * @param Url    $url
     * @param mixed  $data
     * @param string $contentType (optional)
     *
     * @return Response
     */
    public function put(Url $url, $data=null, $contentType=null);

    /**
     * Perform a patch request. Send $data (raw). It should be send in
     * $contentType.
     *
     * @param Url    $url
     * @param mixed  $data
     * @param string $contentType (optional)
     *
     * @return Response
     */
    public function patch(Url $url, $data=null, $contentType=null);

    /**
     * Perform a patch request. Send $data (raw). It should be send in
     * $contentType.
     *
     * @param Url    $url
     * @param mixed  $data
     * @param string $contentType (optional)
     *
     * @return Response
     */
    public function delete(Url $url, $data=null, $contentType=null);

    /**
     * Perform a post request. Post $data (raw). It should be send in
     * $contentType.
     *
     * @param Url    $url
     * @param mixed  $data
     *
     * @return Response
     */
    public function submit(Url $url, array $data);

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
    public function header($header, $value=null);
}