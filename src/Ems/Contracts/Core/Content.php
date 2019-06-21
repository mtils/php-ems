<?php

namespace Ems\Contracts\Core;

use Countable;
use IteratorAggregate;

/**
 * Content represents the content of another object. Contents of a file, content
 * page of a row in a database...
 * count(): the amount of bytes with binary contents, of chars in strings
 * getIterator(): Return every line in ascii files, chunks of data in binary
 * __toString(): Just return the file content in one piece
 **/
interface Content extends Countable, IteratorAggregate, Stringable
{
    /**
     * Return the mimeType of this content
     *
     * @return string
     **/
    public function mimeType();

    /**
     * Return the url of the source which this content belongs (normally file::///foo)
     *
     * @return string
     **/
    public function url();

    /**
     * Return the used stream,
     *
     * @return Stream
     */
    public function getStream();
}
