<?php
/**
 *  * Created by mtils on 15.07.19 at 14:54.
 **/

namespace Ems\Contracts\Core;


use Countable;
use IteratorAggregate;

/**
 * Interface Response
 *
 * The core response is an object just to transport some payload and/or some data
 * back from the application.
 *
 * @package Ems\Contracts\Core
 */
interface Response extends Stringable, ArrayData, Countable, IteratorAggregate
{
    /**
     * Return the content type of this response.
     *
     * @return string
     */
    public function contentType();

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
}