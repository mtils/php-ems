<?php

namespace Ems\Contracts\Core;

use Ems\Contracts\Core\Url;

/**
 * This is the base interface for any connection. May it database, ftp, file,...
 * A Connection must *never* open a connection in __construct(), is has
 * to open when reading or writing on it or an an explicit call to open().
 **/
interface Connection
{
    /**
     * Opens the connection.
     *
     * @return self
     **/
    public function open();

    /**
     * Closes the connection.
     *
     * @return self
     **/
    public function close();

    /**
     * Check if the connection is opened.
     *
     * @return bool
     **/
    public function isOpen();

    /**
     * Return the underlying resource. This could be a real resource or an object.
     *
     * @return resource|object|null
     **/
    public function resource();

    /**
     * Return the url of this connection. You should represent any connection
     * target as an url, like database or local connections.
     *
     * @return Url
     **/
    public function url();

}

