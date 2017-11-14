<?php
/**
 * Created by PhpStorm.
 * User: michi
 * Date: 10.11.17
 * Time: 17:40
 */

namespace Ems\Contracts\Http;

use Ems\Contracts\Core\Stringable;
use Ems\Contracts\Core\ArrayData;
use Ems\Contracts\Core\Serializer;
use IteratorAggregate;
use Countable;

/**
 * Interface Response
 *
 * The response object is used to render the application output and to provide
 * an object for server responses.
 * Like in almost every class of this framework it cares more about hiding
 * implementation details from the user of this class.
 * The response tries to behave like an array if you await an array as response.
 * It behaves as a string if you just want to have the returned body.
 *
 *
 *
 * @package Ems\Contracts\Http
 */
interface Response extends Stringable, ArrayData, Countable, IteratorAggregate
{

    /**
     * Return the HTTP status code
     *
     * @return int
     */
    public function status();

    /**
     * Return the content type of this response.
     *
     * @return string
     */
    public function contentType();

    /**
     * Return the headers.
     *
     * @return mixed
     */
    public function headers();

    /**
     * Return a content object for stream reading.
     *
     * @return \Ems\Contracts\Core\Content
     */
    public function content();

    /**
     * Return the (raw) response body. Like __toString().
     * If no body was set, it tries to renders the payload into the body.
     *
     * @return string
     */
    public function body();

    /**
     * Returns the complete received document.
     *
     * @return string
     */
    public function raw();

    /**
     * Returns the native php data which should be sent with the response.
     * So if you have a view, which could be rendered the view should be assigned
     * to the payload and its __toString() output should be assigned to body.
     * If you have an api which passes data arround you would add the objects
     * or arrays to the payload. They will then be serialized into the body.
     *
     * @return mixed
     */
    public function payload();

    /**
     * Return the serializer to auto serialize or deserialize the payload.
     *
     * @return Serializer
     */
    public function getSerializer();

    /**
     * Set the Serializer to auto de/serialize the body / payload
     *
     * @param Serializer $serializer
     *
     * @return self
     */
    public function setSerializer(Serializer $serializer);
}