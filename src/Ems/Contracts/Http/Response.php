<?php
/**
 * Created by PhpStorm.
 * User: michi
 * Date: 10.11.17
 * Time: 17:40
 */

namespace Ems\Contracts\Http;

use Ems\Contracts\Core\Response as CoreResponse;
use Ems\Contracts\Core\Serializer;

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
interface Response extends CoreResponse
{
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